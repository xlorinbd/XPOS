<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Card / gateway payments taken at the counter stay "pending" until someone confirms that the
 * money has actually settled; only then does it count in the gateway account.
 */
class PaymentConfirmController extends Controller
{
    private function allowed(): bool
    {
        // only Admin, Manager and the Accountant confirm that card / gateway money really arrived
        $role = Role::find(Auth::user()->role_id);
        return $role && (Auth::user()->role_id <= 2 || $role->name === 'Accountant') && $role->hasPermissionTo('account-index');
    }

    public function index(Request $request)
    {
        if (!$this->allowed()) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $tab = $request->input('tab', 'pending');
        $rows = Payment::query()
            ->join('accounts', 'accounts.id', '=', 'payments.account_id')
            ->leftJoin('sales', 'sales.id', '=', 'payments.sale_id')
            ->leftJoin('pre_orders', 'pre_orders.id', '=', 'payments.pre_order_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', DB::raw('COALESCE(sales.warehouse_id, pre_orders.to_warehouse_id)'))
            ->leftJoin('users as confirmer', 'confirmer.id', '=', 'payments.confirmed_by')
            ->where(fn($q) => $q->whereNotNull('payments.sale_id')->orWhereNotNull('payments.pre_order_id'))
            ->where('payments.confirm_status', $tab === 'confirmed' ? 'confirmed' : 'pending')
            ->when($tab === 'confirmed', fn($q) => $q->whereNotNull('payments.confirmed_at'))
            ->orderByDesc('payments.id')
            ->limit(500)
            ->get([
                'payments.id', 'payments.amount', 'payments.paying_method', 'payments.payment_reference', 'payments.created_at',
                'payments.confirmed_at', 'payments.confirm_note', 'accounts.name as account_name',
                DB::raw("COALESCE(sales.reference_no, CONCAT(pre_orders.order_no, ' (advance)')) as sale_reference"), 'sales.id as sale_id', 'warehouses.name as branch_name', 'confirmer.name as confirmed_by_name',
            ]);
        $pendingTotal = (float) Payment::where(fn($q) => $q->whereNotNull('sale_id')->orWhereNotNull('pre_order_id'))->where('confirm_status', 'pending')->sum('amount');

        return view('backend.payment_confirm.index', compact('rows', 'tab', 'pendingTotal'));
    }

    public function confirm(Request $request, $id)
    {
        if (!$this->allowed()) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
        $request->validate(['confirm_note' => 'nullable|string|max:191']);

        $done = DB::transaction(function () use ($id, $request) {
            $payment = Payment::where('id', $id)->where('confirm_status', 'pending')->lockForUpdate()->first();
            if (!$payment) {
                return false;
            }
            $payment->confirm_status = 'confirmed';
            $payment->confirmed_by = Auth::id();
            $payment->confirmed_at = now();
            $payment->confirm_note = $request->input('confirm_note');
            $payment->save();
            return true;
        });

        return redirect()->route('payments.pending')->with($done ? 'message' : 'not_permitted', $done ? 'Payment confirmed. The money is now in the account.' : 'This payment was already confirmed.');
    }
}
