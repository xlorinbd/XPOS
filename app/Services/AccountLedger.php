<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\MoneyTransfer;
use App\Models\Payment;
use App\Models\Returns;
use Illuminate\Support\Facades\DB;

/**
 * Account balances are derived, never stored: every payment, expense, income, return and cash transfer
 * is a ledger line. This class is the single place that adds them up.
 *
 * Cash transfer rules:
 *  - the sender is debited the full amount as soon as the transfer is made (money in transit);
 *  - the receiver is credited only what was accepted;
 *  - anything not accepted is refunded to the sender, but only after the sender accepts the refund;
 *  - cancelled transfers and expense-only records ("recorded") never touch a balance.
 */
class AccountLedger
{
    public const DEBIT_STATUSES = ['pending', 'accepted', 'rejected', 'completed'];

    public function transferDebit(int $accountId, $before = null): float
    {
        return (float) MoneyTransfer::where('from_account_id', $accountId)
            ->whereIn('status', self::DEBIT_STATUSES)
            ->when($before, fn($q) => $q->where('created_at', '<', $before))->sum('amount');
    }

    public function transferCredit(int $accountId, $before = null): float
    {
        $received = (float) MoneyTransfer::where('to_account_id', $accountId)
            ->when($before, fn($q) => $q->where(DB::raw('COALESCE(responded_at, created_at)'), '<', $before))->sum('accepted_amount');
        $refunded = (float) MoneyTransfer::where('from_account_id', $accountId)
            ->where('refund_status', 'completed')
            ->when($before, fn($q) => $q->where(DB::raw('COALESCE(refund_completed_at, updated_at)'), '<', $before))->sum('refund_amount');
        return $received + $refunded;
    }

    /**
     * Money that left this account but has not reached anyone yet (awaiting acceptance or awaiting a refund).
     */
    public function inTransit(int $accountId): float
    {
        return (float) MoneyTransfer::where('from_account_id', $accountId)->where('status', 'pending')->sum('amount')
             + (float) MoneyTransfer::where('from_account_id', $accountId)->where('refund_status', 'pending')->sum('refund_amount');
    }

    /**
     * Card / gateway money taken at the counter that has not been confirmed as settled yet.
     */
    public function pendingConfirmation(int $accountId): float
    {
        return (float) Payment::where(fn($q) => $q->whereNotNull('sale_id')->orWhereNotNull('pre_order_id'))
            ->where('account_id', $accountId)->where('confirm_status', 'pending')->sum('amount');
    }

    public function balance(Account $account, $before = null): float
    {
        $totals = $this->totals($account, $before);
        return round($totals['credit'] - $totals['debit'], 2);
    }

    /**
     * Everything that came into / went out of the account, optionally only up to (not including) $before.
     *
     * @return array{credit: float, debit: float}
     */
    public function totals(Account $account, $before = null): array
    {
        $id = $account->id;
        $upTo = fn($q, string $column) => $before ? $q->where(DB::raw($column), '<', $before) : $q;
        // a card / gateway payment counts from the moment it was confirmed
        $paidAt = 'COALESCE(confirmed_at, created_at)';

        $credit = (float) $upTo(Payment::whereNotNull('sale_id')->where('account_id', $id)->where('confirm_status', '!=', 'pending'), $paidAt)->sum('amount')
            + (float) $upTo(DB::table('return_purchases')->where('account_id', $id), 'created_at')->sum('grand_total')
            + (float) $upTo(Payment::whereNull('sale_id')->whereNull('purchase_id')->whereNotNull('pre_order_id')
                ->where('account_id', $id)->where('confirm_status', '!=', 'pending'), $paidAt)->sum('amount')
            + (float) $upTo(Income::where('account_id', $id), 'created_at')->sum('amount')
            + (float) ($account->initial_balance ?? 0)
            + $this->transferCredit($id, $before);

        $salesReturnRefunds = 0.0;
        foreach ($upTo(Returns::with('sale')->where('account_id', $id), 'created_at')->get() as $return) {
            $sale = $return->sale;
            if (!$sale) {
                continue;
            }
            $due = $sale->grand_total - $sale->paid_amount;
            $refund = $return->grand_total - min($due, $return->grand_total);
            if ($refund > 0) {
                $salesReturnRefunds += $refund;
            }
        }

        $debit = $salesReturnRefunds
            + (float) $upTo(Payment::whereNotNull('purchase_id')->where('account_id', $id), 'created_at')->sum('amount')
            + (float) $upTo(DB::table('pre_orders')->where('advance_refund_account_id', $id), 'COALESCE(advance_refunded_at, updated_at)')->sum('advance_refund_amount')
            + (float) $upTo(Expense::where('account_id', $id), 'created_at')->sum('amount')
            + (float) $upTo(DB::table('payrolls')->where('account_id', $id), 'created_at')->sum('amount')
            + $this->transferDebit($id, $before);

        return ['credit' => (float) $credit, 'debit' => (float) $debit];
    }

    /**
     * Statement lines for cash transfers, shaped like the other statement rows.
     *
     * @return \Illuminate\Support\Collection of stdClass {reference_no, amount, created_at, type}
     */
    public function statementRows(int $accountId, $start, $end, string $filter = '0')
    {
        $rows = collect();
        $wantCredit = in_array($filter, ['0', '2'], true);
        $wantDebit = in_array($filter, ['0', '1'], true);

        $line = function ($ref, $amount, $at, $type) {
            $o = new \stdClass();
            $o->reference_no = $ref;
            $o->amount = $amount;
            $o->created_at = $at;
            $o->type = $type;
            return $o;
        };

        if ($wantDebit) {
            foreach (MoneyTransfer::where('from_account_id', $accountId)->whereIn('status', self::DEBIT_STATUSES)
                ->whereBetween('created_at', [$start, $end])->get() as $t) {
                $tag = $t->status === 'pending' ? ' (awaiting acceptance)' : '';
                $rows->push($line($t->reference_no . $tag, (float) $t->amount, $t->created_at, 'debit'));
            }
        }

        if ($wantCredit) {
            foreach (MoneyTransfer::where('to_account_id', $accountId)->where('accepted_amount', '>', 0)->get() as $t) {
                $at = $t->responded_at ?? $t->created_at;
                if ($at->between($start, $end)) {
                    $rows->push($line($t->reference_no, (float) $t->accepted_amount, $at, 'credit'));
                }
            }
            foreach (MoneyTransfer::where('from_account_id', $accountId)->where('refund_status', 'completed')->get() as $t) {
                $at = $t->refund_completed_at ?? $t->updated_at;
                if ($at->between($start, $end)) {
                    $rows->push($line($t->reference_no . ' (refund)', (float) $t->refund_amount, $at, 'credit'));
                }
            }
        }

        // pre-order advances held for a customer (until the sale is made they are not linked to a sale)
        if ($wantCredit) {
            $advances = Payment::whereNull('sale_id')->whereNotNull('pre_order_id')->where('account_id', $accountId)
                ->where('confirm_status', '!=', 'pending')->whereBetween('created_at', [$start, $end])->get();
            foreach ($advances as $p) {
                $rows->push($line($p->payment_reference . ' (pre-order advance)', (float) $p->amount, $p->created_at, 'credit'));
            }
        }
        if ($wantDebit) {
            foreach (DB::table('pre_orders')->where('advance_refund_account_id', $accountId)->where('advance_refund_amount', '>', 0)->get() as $po) {
                $at = \Carbon\Carbon::parse($po->advance_refunded_at ?? $po->updated_at);
                if ($at->between($start, $end)) {
                    $rows->push($line($po->order_no . ' (advance refund)', (float) $po->advance_refund_amount, $at, 'debit'));
                }
            }
        }

        return $rows;
    }
}
