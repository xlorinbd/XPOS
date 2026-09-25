<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The dashboard the client asked for. Admin and Manager see every branch together (or pick one);
 * everyone else only sees their own branch(es). Cost and profit are shown only to people who may see cost.
 */
class KgDashboardService
{
    public function __construct(private AccountLedger $ledger)
    {
    }

    /** null = every branch. */
    public function scope(User $user, ?int $pickedBranch = null): ?array
    {
        if ((int) $user->role_id <= 2) {
            return $pickedBranch ? [$pickedBranch] : null;
        }
        $ids = $user->branches()->pluck('warehouses.id')->map(fn($i) => (int) $i)->all();
        if ($user->warehouse_id) {
            $ids[] = (int) $user->warehouse_id;
        }
        $ids = array_values(array_unique($ids));
        return $ids ?: [0];
    }

    public function overview(User $user, ?int $pickedBranch = null): array
    {
        $ids = $this->scope($user, $pickedBranch);
        $inScope = fn($q, string $col) => $ids === null ? $q : $q->whereIn($col, $ids);

        $monthStart = Carbon::now()->startOfMonth();
        $todayStart = Carbon::today();
        $now = Carbon::now()->endOfDay();

        $sales = fn(Carbon $from) => $inScope(DB::table('sales')->whereNull('deleted_at')->where('sale_status', 1)->where('created_at', '>=', $from)->where('created_at', '<=', $now), 'warehouse_id');
        $purchases = fn(Carbon $from) => $inScope(DB::table('purchases')->whereNull('deleted_at')->where('created_at', '>=', $from)->where('created_at', '<=', $now), 'warehouse_id');
        $expenses = fn(Carbon $from) => $inScope(DB::table('expenses')->where('created_at', '>=', $from)->where('created_at', '<=', $now), 'warehouse_id');

        $out = [
            'branch_scope' => $ids,
            'month' => [
                'sales' => (float) $sales($monthStart)->sum('grand_total'),
                'invoices' => (int) $sales($monthStart)->count(),
                'purchase' => (float) $purchases($monthStart)->sum('grand_total'),
                'expense' => (float) $expenses($monthStart)->sum('amount'),
            ],
            'today' => [
                'sales' => (float) $sales($todayStart)->sum('grand_total'),
                'invoices' => (int) $sales($todayStart)->count(),
                'purchase' => (float) $purchases($todayStart)->sum('grand_total'),
                'expense' => (float) $expenses($todayStart)->sum('amount'),
            ],
        ];

        if (can_view_cost()) {
            $out['today']['profit'] = $this->profit($todayStart, $now, $ids);
            $out['month']['profit'] = $this->profit($monthStart, $now, $ids);
        }

        // products and stock
        $stock = $inScope(DB::table('product_warehouse')->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->where('products.is_active', true)->where('product_warehouse.qty', '>', 0), 'product_warehouse.warehouse_id');
        $out['products'] = (int) DB::table('products')->where('is_active', true)->count();
        $out['stock'] = (float) $stock->sum('product_warehouse.qty');

        $low = $inScope(DB::table('products')->join('product_warehouse', 'product_warehouse.product_id', '=', 'products.id')
            ->where('products.is_active', true)->where('products.alert_quantity', '>', 0), 'product_warehouse.warehouse_id')
            ->groupBy('products.id', 'products.alert_quantity')
            ->havingRaw('SUM(product_warehouse.qty) <= products.alert_quantity')
            ->select('products.id')->get();
        $out['low_stock'] = $low->count();

        // things waiting
        $out['pending_warranty'] = (int) $inScope(DB::table('service_jobs')->whereNull('deleted_at')->whereIn('status', ['received', 'under_service']), 'warehouse_id')->count();
        $out['pending_purchase'] = (int) $inScope(DB::table('purchases')->whereNull('deleted_at')->whereIn('status', [2, 3, 4]), 'warehouse_id')->count();
        $out['shipments_in_transit'] = (int) Shipment::where('status', 'in_transit')->count();
        $out['pending_transfer'] = (int) $inScope(DB::table('transfers')->whereIn('status', [2, 3]), 'from_warehouse_id')->count();
        $out['pending_incoming'] = (int) $inScope(DB::table('transfers')->whereIn('status', [2, 3]), 'to_warehouse_id')->count();
        $pendingCash = app(MoneyTransferService::class)->pendingCounts($user);
        $out['cash_incoming'] = (int) ($pendingCash['incoming'] ?? 0) + (int) ($pendingCash['refunds'] ?? 0);
        $out['pending_gateway'] = (int) DB::table('payments')->where('confirm_status', 'pending')->count();

        // branch-wise sales / stock (this month) - only worth a table when several branches are in view
        $branches = Warehouse::where('is_active', true)->when($ids !== null, fn($q) => $q->whereIn('id', $ids))->orderBy('name')->get();
        $salesBy = DB::table('sales')->whereNull('deleted_at')->where('sale_status', 1)->where('created_at', '>=', $monthStart)
            ->select('warehouse_id', DB::raw('SUM(grand_total) as total'), DB::raw('COUNT(*) as n'))->groupBy('warehouse_id')->get()->keyBy('warehouse_id');
        $stockBy = DB::table('product_warehouse')->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->where('products.is_active', true)->where('product_warehouse.qty', '>', 0)
            ->select('product_warehouse.warehouse_id', DB::raw('SUM(product_warehouse.qty) as qty'))->groupBy('product_warehouse.warehouse_id')->get()->keyBy('warehouse_id');
        $out['branches'] = $branches->map(fn($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'sales' => (float) ($salesBy[$b->id]->total ?? 0),
            'invoices' => (int) ($salesBy[$b->id]->n ?? 0),
            'stock' => (float) ($stockBy[$b->id]->qty ?? 0),
        ])->all();

        // cash summary: the accounts inside the branches in view (company accounts too for Admin / Manager viewing everything)
        $accounts = Account::where('is_active', true)->where('type', '!=', 'Staff Wallet')
            ->when($ids !== null, fn($q) => $q->whereIn('warehouse_id', $ids))->orderBy('name')->get();
        $rows = [];
        $total = 0.0;
        foreach ($accounts as $a) {
            $bal = $this->ledger->balance($a);
            if (abs($bal) < 0.005 && $ids === null) {
                continue; // empty accounts are noise on the company-wide view
            }
            $rows[] = ['name' => $a->name, 'balance' => $bal];
            $total += $bal;
        }
        $out['cash'] = ['rows' => $rows, 'total' => $total];

        return $out;
    }

    /** Sales - cost of what was sold - expenses - written-off damage, for the period. */
    private function profit(Carbon $from, Carbon $to, ?array $ids): float
    {
        $lines = DB::table('product_sales')
            ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
            ->join('products', 'products.id', '=', 'product_sales.product_id')
            ->whereNull('sales.deleted_at')->where('sales.sale_status', 1)
            ->where('sales.created_at', '>=', $from)->where('sales.created_at', '<=', $to)
            ->when($ids !== null, fn($q) => $q->whereIn('sales.warehouse_id', $ids))
            ->select('product_sales.imei_number', 'product_sales.qty', 'product_sales.total', 'products.cost')
            ->get();

        $revenue = 0.0;
        $cost = 0.0;
        foreach ($lines as $l) {
            $revenue += (float) $l->total;
            $serials = array_filter(array_map('trim', explode(',', (string) $l->imei_number)));
            $serialCost = 0.0;
            $found = 0;
            if ($serials) {
                $rows = DB::table('product_serials')->whereIn('serial_number', $serials)->get(['purchase_cost', 'landed_cost', 'extra_cost']);
                foreach ($rows as $r) {
                    $serialCost += (float) $r->purchase_cost + (float) $r->landed_cost + (float) $r->extra_cost;
                    $found++;
                }
            }
            // a unit without its own cost (untracked stock) is costed at the product's average cost
            $cost += $found > 0 && $serialCost > 0 ? $serialCost : (float) $l->cost * (float) $l->qty;
        }
        $expense = (float) DB::table('expenses')->where('created_at', '>=', $from)->where('created_at', '<=', $to)
            ->when($ids !== null, fn($q) => $q->whereIn('warehouse_id', $ids))->sum('amount');

        return round($revenue - $cost - $expense - kg_damage_loss($from, $to, $ids && count($ids) === 1 ? $ids[0] : 0), 2);
    }
}
