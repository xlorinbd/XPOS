<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Product_Warehouse;
use App\Models\Transfer;
use App\Models\ProductTransfer;
use App\Models\SupplierRma;
use Illuminate\Support\Facades\DB;

class WarehouseValuationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
    }

    public function index(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $warehouses = Warehouse::where('is_active', true)->get();

        $selectedWarehouse = null;
        if ($warehouseId) {
            $selectedWarehouse = Warehouse::find($warehouseId);
        }

        $valuationData = [];
        $targetWarehouses = $selectedWarehouse ? collect([$selectedWarehouse]) : $warehouses;

        $grandSalableValue = 0;
        $grandInTransitValue = 0;
        $grandDamagedValue = 0;
        $grandPendingRmaValue = 0;
        $grandTotalValue = 0;

        foreach ($targetWarehouses as $wh) {
            // 1. Serialized Salable (Available) - Specific Identification Method
            $availableSerials = ProductSerial::where('warehouse_id', $wh->id)
                ->where('status', 'available')
                ->with('product:id,name,code,cost')
                ->get();

            $serializedSalableValue = 0;
            foreach ($availableSerials as $s) {
                $cost = null;
                if ($s->purchase_id) {
                    $pp = DB::table('product_purchases')
                        ->where('purchase_id', $s->purchase_id)
                        ->where('product_id', $s->product_id)
                        ->first();
                    if ($pp && $pp->net_unit_cost) {
                        $cost = (float)$pp->net_unit_cost;
                    }
                }
                if (!$cost && $s->product) {
                    $cost = (float)$s->product->cost;
                }
                $serializedSalableValue += ($cost ?? 0);
            }

            // 2. Non-Serialized Salable (Available) - Weighted Average / Product Cost
            $nonSerializedRows = Product_Warehouse::where('warehouse_id', $wh->id)
                ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                ->where(function($q) {
                    $q->whereNull('products.is_imei')->orWhere('products.is_imei', 0);
                })
                ->where('product_warehouse.qty', '>', 0)
                ->select('product_warehouse.qty', 'products.cost')
                ->get();

            $nonSerializedSalableValue = 0;
            foreach ($nonSerializedRows as $row) {
                $nonSerializedSalableValue += ($row->qty * $row->cost);
            }

            $totalSalableValue = $serializedSalableValue + $nonSerializedSalableValue;

            // 3. In-Transit Transfer Value (Dispatched from this warehouse, not yet received)
            $transfersSent = Transfer::where('from_warehouse_id', $wh->id)
                ->where('status', 3) // Sent
                ->pluck('id');

            $inTransitValue = 0;
            if ($transfersSent->isNotEmpty()) {
                $inTransitValue = ProductTransfer::whereIn('transfer_id', $transfersSent)
                    ->sum(DB::raw('qty * net_unit_cost'));
            }

            // 4. In-Store Damaged Asset Value
            $damagedSerials = ProductSerial::where('warehouse_id', $wh->id)
                ->where('status', 'damaged')
                ->with('product:id,name,code,cost')
                ->get();

            $damagedValue = 0;
            foreach ($damagedSerials as $s) {
                $cost = null;
                if ($s->purchase_id) {
                    $pp = DB::table('product_purchases')
                        ->where('purchase_id', $s->purchase_id)
                        ->where('product_id', $s->product_id)
                        ->first();
                    if ($pp && $pp->net_unit_cost) {
                        $cost = (float)$pp->net_unit_cost;
                    }
                }
                if (!$cost && $s->product) {
                    $cost = (float)$s->product->cost;
                }
                $damagedValue += ($cost ?? 0);
            }

            // 5. Pending Vendor RMA (Dispatched to suppliers from this warehouse)
            $pendingRmas = SupplierRma::where('warehouse_id', $wh->id)
                ->where('status', 'dispatched')
                ->get();
            $pendingRmaValue = $pendingRmas->sum('purchase_cost');

            $warehouseTotal = $totalSalableValue + $inTransitValue + $damagedValue + $pendingRmaValue;

            $valuationData[] = [
                'warehouse' => $wh,
                'available_serials_count' => $availableSerials->count(),
                'serialized_salable_value' => $serializedSalableValue,
                'non_serialized_salable_value' => $nonSerializedSalableValue,
                'total_salable_value' => $totalSalableValue,
                'in_transit_value' => $inTransitValue,
                'damaged_value' => $damagedValue,
                'pending_rma_value' => $pendingRmaValue,
                'total_valuation' => $warehouseTotal,
            ];

            $grandSalableValue += $totalSalableValue;
            $grandInTransitValue += $inTransitValue;
            $grandDamagedValue += $damagedValue;
            $grandPendingRmaValue += $pendingRmaValue;
            $grandTotalValue += $warehouseTotal;
        }

        return view('backend.report.warehouse_stock_valuation', compact(
            'warehouses',
            'warehouseId',
            'valuationData',
            'grandSalableValue',
            'grandInTransitValue',
            'grandDamagedValue',
            'grandPendingRmaValue',
            'grandTotalValue'
        ));
    }
}
