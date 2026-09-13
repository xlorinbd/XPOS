<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupplierRma;
use App\Models\DamageRecord;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\CashRegister;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierRmaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $warehouseId = $request->input('warehouse_id');
        $supplierId = $request->input('supplier_id');

        $query = SupplierRma::with(['supplier', 'warehouse', 'product', 'serial', 'user'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $rmas = $query->paginate(20);
        $warehouses = Warehouse::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();

        return view('backend.supplier_rma.index', compact('rmas', 'warehouses', 'suppliers', 'status', 'warehouseId', 'supplierId'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('backend.supplier_rma.create', compact('suppliers', 'warehouses'));
    }

    public function show($id)
    {
        $supplierRma = SupplierRma::with(['supplier', 'warehouse', 'product', 'serial', 'user'])->findOrFail($id);
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($supplierRma);
        }
        return view('backend.supplier_rma.show', compact('supplierRma'));
    }

    public function getDamagedSerials(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $query = ProductSerial::where('status', 'damaged');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $serials = $query->with('product:id,name,code,cost')->get();

        // Calculate accurate purchase cost for each serial
        $result = $serials->map(function ($s) {
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

            return [
                'id' => $s->id,
                'serial_number' => $s->serial_number,
                'product_id' => $s->product_id,
                'product_name' => $s->product ? $s->product->name : 'N/A',
                'product_code' => $s->product ? $s->product->code : 'N/A',
                'purchase_cost' => $cost ?? 0,
                'detailed_condition' => $s->detailed_condition,
            ];
        });

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'serial_number' => 'required|string',
            'reason' => 'required|string|max:191',
            'tracking_number' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        $sn = trim($request->serial_number);

        DB::beginTransaction();
        try {
            $serial = ProductSerial::where('serial_number', $sn)
                ->lockForUpdate()
                ->first();

            if (!$serial) {
                DB::rollBack();
                return response()->json(['error' => "Serial '{$sn}' not found."], 422);
            }

            // Must be strictly 'damaged' (Store-owned defective stock only)
            if ($serial->status !== 'damaged') {
                DB::rollBack();
                return response()->json([
                    'error' => "Serial '{$sn}' is currently '{$serial->status}' and cannot be sent for vendor RMA (must be 'damaged')."
                ], 422);
            }

            $product = Product::find($serial->product_id);

            // Accurate purchase cost sourcing
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

            // Find any associated damage record
            $damageRecord = DamageRecord::where('product_serial_id', $serial->id)
                ->where('status', 'logged')
                ->latest()
                ->first();

            // Transition serial to 'returned_to_vendor'
            $serial->markAsReturnedToVendor();

            if ($damageRecord) {
                $damageRecord->status = 'in_rma';
                $damageRecord->save();
            }

            // Unique RMA number with retry loop
            $rmaNo = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = 'RTV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                if (!SupplierRma::where('rma_no', $candidate)->exists()) {
                    $rmaNo = $candidate;
                    break;
                }
            }
            if (!$rmaNo) {
                $rmaNo = 'RTV-' . date('Ymd') . '-' . time();
            }

            $rma = SupplierRma::create([
                'rma_no' => $rmaNo,
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'damage_record_id' => $damageRecord ? $damageRecord->id : null,
                'product_id' => $product->id,
                'product_serial_id' => $serial->id,
                'serial_number' => $sn,
                'purchase_cost' => $purchaseCost,
                'reason' => $request->reason,
                'tracking_number' => $request->tracking_number,
                'status' => 'dispatched',
                'dispatched_at' => Carbon::now(),
                'user_id' => Auth::id(),
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "RTV #{$rmaNo} created. Serial '{$sn}' dispatched to vendor.",
                'rma_id' => $rma->id,
                'rma_no' => $rmaNo,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'RTV dispatch failed: ' . $e->getMessage()], 422);
        }
    }

    public function resolve(Request $request, $id)
    {
        $request->validate([
            'resolution_type' => 'required|in:replaced,refunded,rejected_returned',
            'replacement_serial_number' => 'nullable|string|required_if:resolution_type,replaced',
            'refund_amount' => 'nullable|numeric|min:0|required_if:resolution_type,refunded',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        $rma = SupplierRma::findOrFail($id);
        if ($rma->status !== 'dispatched') {
            return response()->json(['error' => 'RMA ticket is already resolved.'], 422);
        }

        $type = $request->resolution_type;
        $product = Product::find($rma->product_id);
        $oldSerial = ProductSerial::find($rma->product_serial_id);

        DB::beginTransaction();
        try {
            if ($type === 'replaced') {
                $newSn = trim($request->replacement_serial_number);

                // Check duplicate new serial
                if (ProductSerial::where('serial_number', $newSn)->exists()) {
                    DB::rollBack();
                    return response()->json(['error' => "Replacement serial '{$newSn}' already exists in system."], 422);
                }

                // Create new available serial
                $newSerial = ProductSerial::create([
                    'product_id' => $rma->product_id,
                    'serial_number' => $newSn,
                    'detailed_condition' => 'Brand New (RMA Replacement from Supplier)',
                    'status' => 'available',
                    'warehouse_id' => $rma->warehouse_id,
                    'purchase_id' => $oldSerial ? $oldSerial->purchase_id : null,
                ]);

                // Sync stock for warehouse (+1 available)
                $product->syncSerialStock($rma->warehouse_id);

                $rma->status = 'replaced';
                $rma->replacement_serial_number = $newSn;
                $rma->loss_amount = 0;
                $rma->resolved_at = Carbon::now();

                if ($rma->damageRecord) {
                    $rma->damageRecord->status = 'resolved';
                    $rma->damageRecord->save();
                }
            } elseif ($type === 'refunded') {
                $refundAmount = (float)$request->refund_amount;
                $purchaseCost = (float)$rma->purchase_cost;
                $loss = max(0, round($purchaseCost - $refundAmount, 2));

                $accountId = $request->account_id;
                if (!$accountId) {
                    $account = Account::where('is_default', true)->first() ?? Account::first();
                    $accountId = $account ? $account->id : 1;
                }

                // If money refunded, credit account
                if ($refundAmount > 0) {
                    $acc = Account::find($accountId);
                    if ($acc) {
                        $acc->total_balance += $refundAmount;
                        $acc->save();
                    }
                }

                // If partial refund -> book difference to Expense as loss
                $expenseId = null;
                if ($loss > 0) {
                    $category = ExpenseCategory::firstOrCreate(
                        ['code' => 'RMA-LOSS'],
                        ['name' => 'Inventory Damage / RMA Loss', 'is_active' => true]
                    );

                    $expense = Expense::create([
                        'reference_no' => 'exp-rma-' . date("Ymd") . '-' . date("his"),
                        'expense_category_id' => $category->id,
                        'warehouse_id' => $rma->warehouse_id,
                        'account_id' => $accountId,
                        'user_id' => Auth::id(),
                        'amount' => $loss,
                        'note' => "Loss from Vendor RMA #{$rma->rma_no} (Cost: ৳{$purchaseCost}, Refund: ৳{$refundAmount})",
                    ]);
                    $expenseId = $expense->id;
                }

                $rma->status = 'refunded';
                $rma->refund_amount = $refundAmount;
                $rma->loss_amount = $loss;
                $rma->expense_id = $expenseId;
                $rma->resolved_at = Carbon::now();

                if ($rma->damageRecord) {
                    $rma->damageRecord->status = 'resolved';
                    $rma->damageRecord->save();
                }
            } elseif ($type === 'rejected_returned') {
                // Returned rejected from vendor -> reverts to 'damaged'
                if ($oldSerial) {
                    $oldSerial->status = 'damaged';
                    $oldSerial->warehouse_id = $rma->warehouse_id;
                    $oldSerial->save();
                }

                $purchaseCost = (float)$rma->purchase_cost;
                $account = Account::where('is_default', true)->first() ?? Account::first();
                $accountId = $account ? $account->id : 1;

                $category = ExpenseCategory::firstOrCreate(
                    ['code' => 'RMA-LOSS'],
                    ['name' => 'Inventory Damage / RMA Loss', 'is_active' => true]
                );

                $expense = Expense::create([
                    'reference_no' => 'exp-rma-' . date("Ymd") . '-' . date("his"),
                    'expense_category_id' => $category->id,
                    'warehouse_id' => $rma->warehouse_id,
                    'account_id' => $accountId,
                    'user_id' => Auth::id(),
                    'amount' => $purchaseCost,
                    'note' => "Complete write-off: Vendor rejected RMA #{$rma->rma_no} for serial {$rma->serial_number}",
                ]);

                $rma->status = 'rejected_returned';
                $rma->loss_amount = $purchaseCost;
                $rma->expense_id = $expense->id;
                $rma->resolved_at = Carbon::now();

                if ($rma->damageRecord) {
                    $rma->damageRecord->status = 'scrapped';
                    $rma->damageRecord->save();
                }
            }

            if ($request->filled('notes')) {
                $rma->notes = ($rma->notes ? $rma->notes . "\n" : '') . "Resolution Note: " . $request->notes;
            }

            $rma->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "RMA #{$rma->rma_no} resolved successfully as " . strtoupper($type),
                'status' => $rma->status,
                'loss_amount' => $rma->loss_amount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'RMA resolution failed: ' . $e->getMessage()], 422);
        }
    }
}
