<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\MoneyTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cash transfers between Khan Gadget accounts.
 *
 *  - Branch<->Branch and Branch<->Warehouse transfers need the receiving side to accept (or reject with a reason).
 *  - All other transfers complete at once.
 *  - Nothing here deletes anything: a transfer ends as completed, accepted/rejected(+refund) or cancelled.
 */
class MoneyTransferService
{
    public function __construct(private AccountLedger $ledger)
    {
    }

    // ---------------------------------------------------------------- who may do what

    public function isGlobal(User $user): bool
    {
        return (int) $user->role_id <= 2;
    }

    /** Branch / warehouse ids the user works in. */
    public function warehouseIds(User $user): array
    {
        $ids = $user->branches()->pluck('warehouses.id')->all();
        if ($user->warehouse_id) {
            $ids[] = (int) $user->warehouse_id;
        }
        return array_values(array_unique(array_map('intval', $ids)));
    }

    /** May this user move money out of / act for the account? */
    public function ownsAccount(User $user, Account $account): bool
    {
        if ($this->isGlobal($user)) {
            return true;
        }
        if ($account->owner_user_id && (int) $account->owner_user_id === (int) $user->id) {
            return true;
        }
        return $account->warehouse_id && in_array((int) $account->warehouse_id, $this->warehouseIds($user), true);
    }

    public function canRespond(User $user, MoneyTransfer $t): bool
    {
        return $this->isGlobal($user) || ($t->to_warehouse_id && in_array((int) $t->to_warehouse_id, $this->warehouseIds($user), true));
    }

    public function canAcceptRefund(User $user, MoneyTransfer $t): bool
    {
        return $this->isGlobal($user) || ($t->from_warehouse_id && in_array((int) $t->from_warehouse_id, $this->warehouseIds($user), true))
            || ($t->fromAccount && $t->fromAccount->owner_user_id && (int) $t->fromAccount->owner_user_id === (int) $user->id);
    }

    public function canCancel(User $user, MoneyTransfer $t): bool
    {
        return $this->isGlobal($user) || (int) $t->created_by === (int) $user->id
            || ($t->from_warehouse_id && in_array((int) $t->from_warehouse_id, $this->warehouseIds($user), true));
    }

    /** Accounts the user may pick as the sending side for a transfer type. */
    public function senderAccounts(User $user, string $typeCode)
    {
        $rule = MoneyTransfer::TYPES[$typeCode] ?? null;
        if (!$rule) {
            return collect();
        }
        return Account::where('is_active', true)->get()
            ->filter(fn(Account $a) => $this->inAnyGroup($a, $rule['from']) && $this->ownsAccount($user, $a))
            ->values();
    }

    /** Accounts that may receive for a transfer type (everyone can see them, only the receiver accepts). */
    public function receiverAccounts(string $typeCode)
    {
        $rule = MoneyTransfer::TYPES[$typeCode] ?? null;
        if (!$rule || !is_array($rule['to'])) {
            return collect();
        }
        return Account::where('is_active', true)->get()->filter(fn(Account $a) => $this->inAnyGroup($a, $rule['to']))->values();
    }

    private function inAnyGroup(Account $account, array $groups): bool
    {
        foreach ($groups as $g) {
            if ($account->inGroup($g)) {
                return true;
            }
        }
        return false;
    }

    // ---------------------------------------------------------------- actions

    /**
     * @param array $data type_code, from_account_id, to_account_id?, amount, note?, carried_by?, third_party_name?,
     *                    third_party_details?, expense_category_id?
     */
    public function create(array $data, User $user): MoneyTransfer
    {
        $rule = MoneyTransfer::TYPES[$data['type_code'] ?? ''] ?? null;
        if (!$rule) {
            throw ValidationException::withMessages(['type_code' => 'Choose a transfer type.']);
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount greater than zero.']);
        }

        $transfer = DB::transaction(function () use ($data, $rule, $amount, $user) {
            $from = Account::lockForUpdate()->find($data['from_account_id'] ?? 0);
            if (!$from || !$from->is_active || !$this->inAnyGroup($from, $rule['from'])) {
                throw ValidationException::withMessages(['from_account_id' => 'This account cannot be used as the sender for this transfer type.']);
            }
            if (!$this->ownsAccount($user, $from)) {
                throw ValidationException::withMessages(['from_account_id' => 'You cannot send money from this account.']);
            }
            if ($this->ledger->balance($from) + 0.0001 < $amount) {
                throw ValidationException::withMessages(['amount' => 'Not enough balance in ' . $from->name . ' (available ' . money($this->ledger->balance($from)) . ').']);
            }

            $to = null;
            if (is_array($rule['to'])) {
                $to = Account::find($data['to_account_id'] ?? 0);
                if (!$to || !$to->is_active || !$this->inAnyGroup($to, $rule['to'])) {
                    throw ValidationException::withMessages(['to_account_id' => 'Choose the receiving account.']);
                }
                if ($to->id === $from->id) {
                    throw ValidationException::withMessages(['to_account_id' => 'The sending and receiving account must be different.']);
                }
                if ($rule['approval']) {
                    if (!$to->warehouse_id) {
                        throw ValidationException::withMessages(['to_account_id' => 'The receiving account is not assigned to a branch or warehouse, so nobody could accept it.']);
                    }
                    if ($data['type_code'] === 'branch_to_branch' && (int) $to->warehouse_id === (int) $from->warehouse_id) {
                        throw ValidationException::withMessages(['to_account_id' => 'Choose an account of a different branch.']);
                    }
                }
            }

            if ($rule['to'] === null && trim((string) ($data['third_party_name'] ?? '')) === '') {
                throw ValidationException::withMessages(['third_party_name' => 'Enter who the money is sent to.']);
            }
            if ($rule['to'] === 'expense' && empty($data['expense_category_id'])) {
                throw ValidationException::withMessages(['expense_category_id' => 'Choose the expense category.']);
            }

            $status = $rule['approval'] ? 'pending' : 'completed';
            $accepted = 0;
            if ($status === 'completed' && $to) {
                $accepted = $amount;
            }
            if ($rule['to'] === 'expense') {
                $status = 'recorded';   // the Expense itself carries the debit
            }

            $transfer = MoneyTransfer::create([
                'reference_no' => $this->nextReference(),
                'type_code' => $data['type_code'],
                'from_account_id' => $from->id,
                'to_account_id' => $to?->id,
                'from_warehouse_id' => $from->warehouse_id,
                'to_warehouse_id' => $to?->warehouse_id,
                'amount' => $amount,
                'status' => $status,
                'accepted_amount' => $accepted,
                'third_party_name' => $data['third_party_name'] ?? null,
                'third_party_details' => $data['third_party_details'] ?? null,
                'carried_by' => $data['carried_by'] ?? null,
                'note' => $data['note'] ?? null,
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            if ($rule['to'] === 'expense') {
                $expense = Expense::create([
                    'reference_no' => 'er-' . date('Ymd') . '-' . date('his') . '-' . $transfer->id,
                    'expense_category_id' => $data['expense_category_id'],
                    'warehouse_id' => $from->warehouse_id ?: ($user->warehouse_id ?: null),
                    'account_id' => $from->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'note' => trim('Paid from staff wallet (' . $transfer->reference_no . '). ' . ($data['note'] ?? '')),
                ]);
                $transfer->update(['expense_id' => $expense->id]);
            }

            return $transfer;
        });

        if ($transfer->status === 'pending') {
            $this->tell($transfer, ['Branch Manager', 'Manager', 'Admin'], $transfer->to_warehouse_id,
                'Cash transfer ' . $transfer->reference_no . ': ' . money($transfer->amount) . ' sent by ' . $user->name . ' is waiting for your acceptance.', $user->id);
        }
        return $transfer;
    }

    /**
     * Receiving side answers. $acceptedAmount = full amount to accept everything, 0 to reject everything,
     * anything in between accepts part and sends the rest back as a refund.
     */
    public function respond(MoneyTransfer $transfer, User $user, float $acceptedAmount, string $receivedByName, ?string $carriedBy, ?string $reason): MoneyTransfer
    {
        $t = DB::transaction(function () use ($transfer, $user, $acceptedAmount, $receivedByName, $carriedBy, $reason) {
            $t = MoneyTransfer::lockForUpdate()->find($transfer->id);
            if ($t->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'This transfer has already been answered or cancelled.']);
            }
            if (!$this->canRespond($user, $t)) {
                throw ValidationException::withMessages(['status' => 'Only the receiving branch / warehouse can accept this transfer.']);
            }
            $receivedByName = trim($receivedByName);
            if ($receivedByName === '') {
                throw ValidationException::withMessages(['received_by_name' => '"Received by" name is required.']);
            }

            $acceptedAmount = round($acceptedAmount, 2);
            $amount = round((float) $t->amount, 2);
            if ($acceptedAmount < 0 || $acceptedAmount > $amount + 0.0001) {
                throw ValidationException::withMessages(['accepted_amount' => 'The accepted amount must be between 0 and ' . $amount . '.']);
            }
            $refund = round($amount - $acceptedAmount, 2);
            if ($refund > 0 && trim((string) $reason) === '') {
                throw ValidationException::withMessages(['response_note' => 'A reason is required when anything is rejected.']);
            }

            $t->update([
                'status' => $acceptedAmount <= 0 ? 'rejected' : 'accepted',
                'accepted_amount' => $acceptedAmount,
                'refund_amount' => $refund,
                'refund_status' => $refund > 0 ? 'pending' : null,
                'responded_by' => $user->id,
                'responded_at' => now(),
                'received_by_name' => $receivedByName,
                'carried_by' => $carriedBy ?: $t->carried_by,
                'response_note' => $reason ?: null,
            ]);

            return $t->fresh();
        });

        if ((float) $t->refund_amount > 0) {
            $msg = 'Cash transfer ' . $t->reference_no . ': ' . money($t->accepted_amount) . ' of ' . money($t->amount) . ' accepted by ' . $user->name
                . '. ' . money($t->refund_amount) . ' is coming back - please accept the refund when it arrives.';
        } else {
            $msg = 'Cash transfer ' . $t->reference_no . ' of ' . money($t->amount) . ' was accepted by ' . $user->name . '.';
        }
        $this->tell($t, ['Branch Manager', 'Manager', 'Admin'], $t->from_warehouse_id, $msg, $user->id);
        return $t;
    }

    /** Sending side confirms the returned money has come back. */
    public function acceptRefund(MoneyTransfer $transfer, User $user): MoneyTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $t = MoneyTransfer::lockForUpdate()->find($transfer->id);
            if ($t->refund_status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'There is no refund waiting for acceptance on this transfer.']);
            }
            if (!$this->canAcceptRefund($user, $t)) {
                throw ValidationException::withMessages(['status' => 'Only the sending side can accept the refund.']);
            }
            $t->update([
                'refund_status' => 'completed',
                'refund_completed_at' => now(),
                'refund_accepted_by' => $user->id,
            ]);
            return $t->fresh();
        });
    }

    /** Sender withdraws a transfer that nobody has answered yet. */
    public function cancel(MoneyTransfer $transfer, User $user): MoneyTransfer
    {
        $t = DB::transaction(function () use ($transfer, $user) {
            $t = MoneyTransfer::lockForUpdate()->find($transfer->id);
            if ($t->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only a transfer that is still awaiting acceptance can be cancelled.']);
            }
            if (!$this->canCancel($user, $t)) {
                throw ValidationException::withMessages(['status' => 'You cannot cancel this transfer.']);
            }
            $t->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            return $t->fresh();
        });

        $this->tell($t, ['Branch Manager', 'Manager', 'Admin'], $t->to_warehouse_id,
            'Cash transfer ' . $t->reference_no . ' was cancelled by the sender; there is nothing to accept.', $user->id);
        return $t;
    }

    private function tell(MoneyTransfer $t, array $roles, $warehouseId, string $message, int $exceptUserId): void
    {
        \App\Services\KgNotifier::toRoles($roles, $message, route('money-transfers.show', $t->id, false), 'cash_transfer', $warehouseId ? (int) $warehouseId : null, $exceptUserId);
    }

    // ---------------------------------------------------------------- indicators

    /**
     * Counts for the menu badges: things waiting for this user.
     *
     * @return array{incoming:int, refunds:int}
     */
    public function pendingCounts(User $user): array
    {
        $ids = $this->warehouseIds($user);
        $incoming = MoneyTransfer::where('status', 'pending')
            ->when(!$this->isGlobal($user), fn($q) => $q->whereIn('to_warehouse_id', $ids ?: [0]))->count();
        $refunds = MoneyTransfer::where('refund_status', 'pending')
            ->when(!$this->isGlobal($user), fn($q) => $q->whereIn('from_warehouse_id', $ids ?: [0]))->count();
        return ['incoming' => $incoming, 'refunds' => $refunds];
    }

    private function nextReference(): string
    {
        $prefix = 'mtr-' . date('Ymd') . '-';
        $n = MoneyTransfer::where('reference_no', 'like', $prefix . '%')->count() + 1;
        do {
            $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (MoneyTransfer::where('reference_no', $ref)->exists());
        return $ref;
    }
}
