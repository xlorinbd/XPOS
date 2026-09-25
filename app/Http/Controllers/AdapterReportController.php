<?php

namespace App\Http\Controllers;

use App\Models\Lookup;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AdapterReportController extends Controller
{
    /**
     * Charger Need List: laptops in stock per charger model versus chargers in stock,
     * shown in total and branch-wise. Shortage = laptops - chargers (never negative).
     */
    public function chargerNeed()
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role || !$role->hasPermissionTo('warehouse-stock-report')) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $user = Auth::user();
        $query = DB::table('product_warehouse as pw')
            ->join('products as p', 'p.id', '=', 'pw.product_id')
            ->where('p.is_active', true)
            ->whereNotNull('p.adapter_model_id')
            ->groupBy('p.adapter_model_id', 'pw.warehouse_id', 'p.is_adapter_item')
            ->selectRaw('p.adapter_model_id as model_id, pw.warehouse_id, p.is_adapter_item as is_charger, SUM(pw.qty) as qty');

        if ($user->role_id > 2 && $user->warehouse_id) {
            $query->where('pw.warehouse_id', $user->warehouse_id);
        }

        $rows = $query->get();

        $models = Lookup::ofType('charger_model')->orderBy('name')->get()->keyBy('id');
        $warehouses = Warehouse::where('is_active', true)->get()->keyBy('id');

        $total = [];
        $branchWise = [];
        foreach ($rows as $r) {
            $qty = (float) $r->qty;
            $key = $r->is_charger ? 'chargers' : 'laptops';

            $total[$r->model_id] = $total[$r->model_id] ?? ['laptops' => 0, 'chargers' => 0];
            $total[$r->model_id][$key] += $qty;

            $branchWise[$r->model_id][$r->warehouse_id] = $branchWise[$r->model_id][$r->warehouse_id] ?? ['laptops' => 0, 'chargers' => 0];
            $branchWise[$r->model_id][$r->warehouse_id][$key] += $qty;
        }

        return view('backend.report.charger_need', compact('models', 'warehouses', 'total', 'branchWise'));
    }
}
