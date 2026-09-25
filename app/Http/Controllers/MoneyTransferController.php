<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\MoneyTransfer;
use App\Services\AccountLedger;
use App\Services\MoneyTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class MoneyTransferController extends Controller
{
    public function __construct(private MoneyTransferService $service, private AccountLedger $ledger)
    {
    }

    private function allowed(string $permission): bool
    {
        $role = Role::find(Auth::user()->role_id);
        return $role && $role->hasPermissionTo($permission);
    }

    private function deny()
    {
        return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    /** Transfers this user is allowed to see. */
    private function visible()
    {
        $user = Auth::user();
        $q = MoneyTransfer::query();
        if ($this->service->isGlobal($user)) {
            return $q;
        }
        $ids = $this->service->warehouseIds($user) ?: [0];
        $ownAccounts = Account::where('owner_user_id', $user->id)->pluck('id')->all() ?: [0];
        return $q->where(function ($w) use ($ids, $user, $ownAccounts) {
            $w->whereIn('from_warehouse_id', $ids)
              ->orWhereIn('to_warehouse_id', $ids)
              ->orWhere('created_by', $user->id)
              ->orWhereIn('from_account_id', $ownAccounts);
        });
    }

    public function index(Request $request)
    {
        if (!$this->allowed('money-transfer')) {
            return $this->deny();
        }

        $user = Auth::user();
        $tab = $request->input('tab', 'all');
        $query = $this->visible()->with(['fromAccount', 'toAccount', 'fromWarehouse', 'toWarehouse']);

        $ids = $this->service->warehouseIds($user) ?: [0];
        $global = $this->service->isGlobal($user);

        switch ($tab) {
            case 'incoming':   // waiting for me to accept
                $query->where('status', 'pending')->when(!$global, fn($q) => $q->whereIn('to_warehouse_id', $ids));
                break;
            case 'refunds':    // returned money waiting for me to accept
                $query->where('refund_status', 'pending')->when(!$global, fn($q) => $q->whereIn('from_warehouse_id', $ids));
                break;
            case 'sent':
                $query->when(!$global, fn($q) => $q->where(fn($w) => $w->whereIn('from_warehouse_id', $ids)->orWhere('created_by', $user->id)));
                break;
            default:
                $tab = 'all';
        }

        $transfers = $query->orderByDesc('id')->limit(500)->get();
        $counts = $this->service->pendingCounts($user);

        return view('backend.money_transfer.index', compact('transfers', 'tab', 'counts'));
    }

    public function create()
    {
        if (!$this->allowed('money-transfer')) {
            return $this->deny();
        }

        $user = Auth::user();
        $types = [];
        foreach (MoneyTransfer::TYPES as $code => $rule) {
            $senders = $this->service->senderAccounts($user, $code)->map(fn($a) => [
                'id' => $a->id, 'name' => $a->name . ($a->warehouse ? ' (' . $a->warehouse->name . ')' : ''), 'balance' => $this->ledger->balance($a),
            ])->values();
            $receivers = $this->service->receiverAccounts($code)->map(fn($a) => [
                'id' => $a->id, 'name' => $a->name . ($a->warehouse ? ' (' . $a->warehouse->name . ')' : ''),
            ])->values();
            $types[$code] = [
                'label' => $rule['label'],
                'approval' => $rule['approval'],
                'target' => is_array($rule['to']) ? 'account' : ($rule['to'] === 'expense' ? 'expense' : 'third_party'),
                'senders' => $senders,
                'receivers' => $receivers,
            ];
        }
        $expenseCategories = ExpenseCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('backend.money_transfer.create', compact('types', 'expenseCategories'));
    }

    public function store(Request $request)
    {
        if (!$this->allowed('money-transfer')) {
            return $this->deny();
        }

        $data = $request->validate([
            'type_code' => 'required|string',
            'from_account_id' => 'required|integer',
            'to_account_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'carried_by' => 'nullable|string|max:191',
            'note' => 'nullable|string',
            'third_party_name' => 'nullable|string|max:191',
            'third_party_details' => 'nullable|string',
            'expense_category_id' => 'nullable|integer',
        ]);

        try {
            $transfer = $this->service->create($data, Auth::user());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        $msg = $transfer->status === 'pending'
            ? 'Transfer ' . $transfer->reference_no . ' sent. The amount is held in transit until the receiving side accepts it.'
            : 'Transfer ' . $transfer->reference_no . ' completed.';

        return redirect()->route('money-transfers.show', $transfer->id)->with('message', $msg);
    }

    public function show($id)
    {
        if (!$this->allowed('money-transfer')) {
            return $this->deny();
        }

        $transfer = $this->visible()->with(['fromAccount', 'toAccount', 'fromWarehouse', 'toWarehouse', 'creator', 'responder'])->findOrFail($id);
        $user = Auth::user();
        $can = [
            'respond' => $transfer->status === 'pending' && $this->allowed('transfer-accept') && $this->service->canRespond($user, $transfer),
            'refund' => $transfer->refund_status === 'pending' && $this->allowed('transfer-accept') && $this->service->canAcceptRefund($user, $transfer),
            'cancel' => $transfer->status === 'pending' && $this->service->canCancel($user, $transfer),
        ];

        return view('backend.money_transfer.show', compact('transfer', 'can'));
    }

    public function respond(Request $request, $id)
    {
        if (!$this->allowed('transfer-accept')) {
            return $this->deny();
        }
        $transfer = $this->visible()->findOrFail($id);

        $request->validate([
            'decision' => 'required|in:accept,partial,reject',
            'received_by_name' => 'required|string|max:191',
            'carried_by' => 'nullable|string|max:191',
            'accepted_amount' => 'nullable|numeric|min:0',
            'response_note' => 'nullable|string',
        ]);

        $amount = (float) $transfer->amount;
        $accepted = match ($request->decision) {
            'accept' => $amount,
            'reject' => 0.0,
            default => (float) $request->input('accepted_amount', 0),
        };
        if ($request->decision === 'partial' && ($accepted <= 0 || $accepted >= $amount)) {
            return redirect()->back()->withInput()->withErrors(['accepted_amount' => 'For a partial acceptance enter an amount above 0 and below ' . $amount . '.']);
        }

        try {
            $this->service->respond($transfer, Auth::user(), $accepted, (string) $request->received_by_name, $request->carried_by, $request->response_note);
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        $msg = $accepted >= $amount ? 'Transfer accepted.' : ($accepted > 0 ? 'Part of the transfer was accepted; the rest is on its way back to the sender.' : 'Transfer rejected. The money is on its way back to the sender.');
        return redirect()->route('money-transfers.show', $id)->with('message', $msg);
    }

    public function acceptRefund($id)
    {
        if (!$this->allowed('transfer-accept')) {
            return $this->deny();
        }
        $transfer = $this->visible()->findOrFail($id);
        try {
            $this->service->acceptRefund($transfer, Auth::user());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
        return redirect()->route('money-transfers.show', $id)->with('message', 'Refund accepted. The money is back in the sending account.');
    }

    public function cancel($id)
    {
        if (!$this->allowed('money-transfer')) {
            return $this->deny();
        }
        $transfer = $this->visible()->findOrFail($id);
        try {
            $this->service->cancel($transfer, Auth::user());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
        return redirect()->route('money-transfers.show', $id)->with('message', 'Transfer cancelled. The amount is back in the sending account.');
    }
}
