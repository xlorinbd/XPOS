<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\MoneyTransfer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\AccountLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Daily Account: one page (and PDF) per branch per day.
 * Opening balance -> sales summary -> sales details (no customer data) -> expenses -> transfers -> final cash summary.
 * The opening balance of a day is always the closing balance of the day before.
 */
class DailyAccountController extends Controller
{
    public function __construct(private AccountLedger $ledger)
    {
    }

    private function branchesForUser()
    {
        $user = Auth::user();
        // Admin / Manager can also look back at branches that were closed
        if ((int) $user->role_id <= 2) {
            return Warehouse::orderByDesc('is_active')->orderBy('name')->get();
        }
        $all = Warehouse::where('is_active', true)->orderBy('name')->get();
        $ids = $user->branches()->pluck('warehouses.id')->map(fn($i) => (int) $i)->all();
        if ($user->warehouse_id) {
            $ids[] = (int) $user->warehouse_id;
        }
        return $all->whereIn('id', $ids)->values();
    }

    private function allowed(): bool
    {
        $role = Role::find(Auth::user()->role_id);
        return $role && ($role->hasPermissionTo('account-statement') || $role->hasPermissionTo('account-index'));
    }

    public function index(Request $request)
    {
        if (!$this->allowed()) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
        $branches = $this->branchesForUser();
        $isGlobal = (int) Auth::user()->role_id <= 2;
        $date = $request->input('date') ? Carbon::parse($request->input('date'))->startOfDay() : Carbon::today();
        $warehouseId = $request->has('warehouse_id') ? (int) $request->input('warehouse_id') : (int) ($branches->first()->id ?? 0);

        if ($warehouseId === 0 && !$isGlobal) {
            $warehouseId = (int) ($branches->first()->id ?? 0);
        }
        if ($warehouseId !== 0 && !$branches->contains('id', $warehouseId)) {
            return redirect()->back()->with('not_permitted', 'You cannot see the accounts of that branch.');
        }

        $report = $this->build($warehouseId, $date);

        if ($request->boolean('pdf')) {
            $pdf = \PDF::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true, 'defaultFont' => 'DejaVu Sans'])
                ->loadView('backend.report.daily_account_pdf', ['r' => $report, 'pdf' => true])
                ->setPaper('a4', 'portrait');
            return $pdf->download('daily-account-' . $date->format('Y-m-d') . '-' . \Str::slug($report['branch_name']) . '.pdf');
        }

        return view('backend.report.daily_account', compact('report', 'branches', 'isGlobal', 'date', 'warehouseId'));
    }

    /** All the numbers for one branch (0 = company accounts) and one day. */
    public function build(int $warehouseId, Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $nextStart = $start->copy()->addDay();

        $branch = $warehouseId ? Warehouse::find($warehouseId) : null;
        $accounts = Account::where('is_active', true)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId), fn($q) => $q->whereNull('warehouse_id'))
            ->where('type', '!=', 'Staff Wallet')
            ->orderBy('name')->get();
        $accountIds = $accounts->pluck('id')->all() ?: [0];

        // ---- opening / movement / closing per account
        $cashRows = [];
        $sum = ['opening' => 0, 'in' => 0, 'out' => 0, 'closing' => 0];
        foreach ($accounts as $a) {
            $before = $this->ledger->totals($a, $start);
            $after = $this->ledger->totals($a, $nextStart);
            $row = [
                'name' => $a->name,
                'opening' => round($before['credit'] - $before['debit'], 2),
                'in' => round($after['credit'] - $before['credit'], 2),
                'out' => round($after['debit'] - $before['debit'], 2),
                'closing' => round($after['credit'] - $after['debit'], 2),
            ];
            $cashRows[] = $row;
            foreach ($sum as $k => $_) {
                $sum[$k] += $row[$k];
            }
        }

        // ---- sales of the day
        $sales = Sale::whereNull('deleted_at')->where('sale_status', 1)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId), fn($q) => $q->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$start, $end])->orderBy('created_at')->get();

        $saleRows = [];
        foreach ($sales as $sale) {
            $methods = Payment::where('sale_id', $sale->id)->pluck('paying_method')->unique()->implode(', ');
            $lines = DB::table('product_sales')->where('sale_id', $sale->id)->get();
            foreach ($lines as $i => $line) {
                $p = Product::find($line->product_id);
                $specs = $p ? implode(' | ', array_filter([$p->processor, $p->ram, $p->storage, $p->display])) : '';
                $saleRows[] = [
                    'invoice' => $sale->reference_no,
                    'time' => Carbon::parse($sale->created_at)->format('h:i A'),
                    'first' => $i === 0,
                    'product' => ($p->name ?? 'Product') . ($p && $p->product_condition ? ' [' . ucfirst($p->product_condition) . ']' : ''),
                    'specs' => $specs,
                    'serial' => ($line->imei_number && !str_contains($line->imei_number, 'null')) ? $line->imei_number : '',
                    'qty' => (float) $line->qty,
                    'unit_price' => (float) $line->net_unit_price,
                    'discount' => (float) $line->discount,
                    'total' => (float) $line->total,
                    'paid_by' => $i === 0 ? $methods : '',
                ];
            }
        }
        $summary = [
            'invoices' => $sales->count(),
            'qty' => (float) $sales->sum('total_qty'),
            'discount' => (float) $sales->sum('total_discount') + (float) $sales->sum('order_discount'),
            'total' => (float) $sales->sum('grand_total'),
            'paid' => (float) $sales->sum('paid_amount'),
        ];
        $summary['due'] = max(0, round($summary['total'] - $summary['paid'], 2));

        // ---- expenses
        $expenses = Expense::with('expenseCategory')
            ->where(function ($q) use ($warehouseId, $accountIds) {
                $q->whereIn('account_id', $accountIds);
                if ($warehouseId) {
                    $q->orWhere('warehouse_id', $warehouseId);
                }
            })
            ->whereBetween('created_at', [$start, $end])->orderBy('created_at')->get();

        // ---- cash transfers that touched these accounts today
        $transfers = MoneyTransfer::with(['fromAccount', 'toAccount'])
            ->where(function ($q) use ($accountIds, $start, $end) {
                $q->where(fn($w) => $w->whereIn('from_account_id', $accountIds)->whereBetween('created_at', [$start, $end]))
                  ->orWhere(fn($w) => $w->whereIn('to_account_id', $accountIds)->where('accepted_amount', '>', 0)
                      ->whereBetween(DB::raw('COALESCE(responded_at, created_at)'), [$start, $end]));
            })
            ->whereNotIn('status', ['cancelled'])->orderBy('created_at')->get();
        $sent = (float) $transfers->filter(fn($t) => in_array($t->from_account_id, $accountIds))->sum('amount');
        $received = (float) $transfers->filter(fn($t) => in_array($t->to_account_id, $accountIds))->sum('accepted_amount');

        return [
            'date' => $date,
            'branch_name' => $branch->name ?? 'Head office (company accounts)',
            'branch' => $branch,
            'cash_rows' => $cashRows,
            'cash_sum' => $sum,
            'summary' => $summary,
            'sale_rows' => $saleRows,
            'expenses' => $expenses,
            'expense_total' => (float) $expenses->sum('amount'),
            'transfers' => $transfers,
            'transfer_sent' => $sent,
            'transfer_received' => $received,
            'account_ids' => $accountIds,
            'generated_by' => Auth::user()->name ?? '',
        ];
    }
}
