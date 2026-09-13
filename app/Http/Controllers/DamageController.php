<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DamageRecord;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DamageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
    }

    public function index(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $status = $request->input('status');

        $query = DamageRecord::with(['product', 'serial', 'warehouse', 'user', 'rma'])
            ->latest();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $damageRecords = $query->paginate(20);
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('backend.damage.index', compact('damageRecords', 'warehouses', 'warehouseId', 'status'));
    }

    public function getAvailableSerials(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $productId = $request->input('product_id');

        $query = ProductSerial::where('status', 'available');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($productId) {
            $query->where('product_id', $productId);
        }

        $serials = $query->select('id', 'product_id', 'serial_number', 'detailed_condition')
            ->with('product:id,name,code,cost')
            ->get();

        return response()->json($serials);
    }

    public function store(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string',
            'damage_reason' => 'required|string|max:191',
            'responsible_person' => 'nullable|string|max:191',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'notes' => 'nullable|string',
        ]);

        $sn = trim($request->serial_number);

        DB::beginTransaction();
        try {
            // Concurrency lock
            $serial = ProductSerial::where('serial_number', $sn)
                ->lockForUpdate()
                ->first();

            if (!$serial) {
                DB::rollBack();
                return response()->json(['error' => "Serial '{$sn}' not found."], 422);
            }

            if ($serial->status !== 'available') {
                DB::rollBack();
                return response()->json([
                    'error' => "Serial '{$sn}' is currently '{$serial->status}' and cannot be marked damaged (must be 'available')."
                ], 422);
            }

            if ($serial->warehouse_id != $request->warehouse_id) {
                DB::rollBack();
                return response()->json(['error' => "Serial '{$sn}' does not belong to the selected warehouse."], 422);
            }

            $product = Product::find($serial->product_id);

            // Accurate Purchase Cost sourcing
            $purchaseCost = null;
            if ($serial->purchase_id) {
                $pp = DB::table('product_purchases')
                    ->where('purchase_id', $serial->purchase_id)
                    ->where('product_id', $serial->product_id)
                    ->first();
                if ($pp && $pp->net_unit_cost) {
                    $purchaseCost = (float)$pp->net_unit_cost;
                }
            }
            if (!$purchaseCost) {
                $purchaseCost = (float)$product->cost;
            }

            // Mark serial as damaged
            $serial->markAsDamaged();

            // Sync stock - deducts from available
            $product->syncSerialStock($serial->warehouse_id);

            // Create Damage Record
            $damage = DamageRecord::create([
                'product_id' => $product->id,
                'product_serial_id' => $serial->id,
                'serial_number' => $sn,
                'warehouse_id' => $serial->warehouse_id,
                'damage_reason' => $request->damage_reason,
                'responsible_person' => $request->responsible_person,
                'damage_cost' => $purchaseCost,
                'status' => 'logged',
                'user_id' => Auth::id(),
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Serial '{$sn}' has been successfully logged as damaged and removed from active stock.",
                'damage_id' => $damage->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Damage logging failed: ' . $e->getMessage()], 422);
        }
    }
}
