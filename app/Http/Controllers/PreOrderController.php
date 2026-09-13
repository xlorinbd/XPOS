<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Payment;
use App\Models\Account;
use App\Models\CashRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $tab = $request->input('tab', 'all');
        $userWarehouseId = Auth::user()->warehouse_id;

        $query = PreOrder::with(['fromWarehouse', 'toWarehouse', 'product', 'customer', 'user', 'sale'])
            ->latest();

        if ($tab === 'incoming' && $userWarehouseId) {
            $query->incoming($userWarehouseId);
        } elseif ($tab === 'outgoing' && $userWarehouseId) {
            $query->outgoing($userWarehouseId);
        }

        if ($status && in_array($status, ['pending', 'confirmed', 'processing', 'ready', 'delivered', 'cancelled'])) {
            $query->where('status', $status);
        }

        $preOrders = $query->paginate(25);
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('backend.pre_order.index', compact('preOrders', 'warehouses', 'status', 'tab'));
    }

    public function create(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::where('is_active', true)->select('id', 'name', 'code', 'price', 'last_border_price')->get();
        $customers = Customer::where('is_active', true)->get();
        $defaultWarehouseId = Auth::user()->warehouse_id ?? ($warehouses->first()->id ?? null);

        $selectedProductId = $request->input('product_id');
        $selectedSerial = $request->input('serial_number');
        $selectedFromWarehouseId = $request->input('from_warehouse_id');

        return view('backend.pre_order.create', compact(
            'warehouses',
            'products',
            'customers',
            'defaultWarehouseId',
            'selectedProductId',
            'selectedSerial',
            'selectedFromWarehouseId'
        ));
    }

    public function show($id)
    {
        $preOrder = PreOrder::with(['fromWarehouse', 'toWarehouse', 'product', 'customer', 'user', 'sale'])->findOrFail($id);
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($preOrder);
        }
        return view('backend.pre_order.show', compact('preOrder'));
    }

    public function interBranchStock($productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        $warehouses = Warehouse::where('is_active', true)->get();
        $branchData = [];

        foreach ($warehouses as $wh) {
            $serials = ProductSerial::where('product_id', $productId)
                ->where('warehouse_id', $wh->id)
                ->where('status', 'available')
                ->select('id', 'serial_number', 'detailed_condition')
                ->get();

            $pw = DB::table('product_warehouse')
                ->where('product_id', $productId)
                ->where('warehouse_id', $wh->id)
                ->first();

            $branchData[] = [
                'warehouse_id' => $wh->id,
                'warehouse_name' => $wh->name,
                'warehouse_phone' => $wh->phone,
                'stock_qty' => $pw ? (float)$pw->qty : 0,
                'available_serials_count' => $serials->count(),
                'serials' => $serials,
            ];
        }

        $specsParts = array_filter([
            $product->processor,
            $product->ram,
            $product->storage,
            $product->display,
            $product->dedicated_graphics
        ]);

        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'price' => (float)$product->price,
            'last_border_price' => (float)($product->last_border_price ?? 0),
            'specs_line' => implode(' | ', $specsParts) ?: 'Standard Specs',
            'product_condition' => strtoupper($product->product_condition ?? 'USED'),
            'branches' => $branchData,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:191',
            'customer_phone' => 'required|string|max:191',
            'product_id' => 'required|integer|exists:products,id',
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id',
            'price' => 'required|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string',
        ]);

        $price = (float)$request->price;
        $advance = (float)($request->advance_amount ?? 0);
        $product = Product::find($request->product_id);

        // Price Floor Protection
        if ($product->last_border_price && $product->last_border_price > 0) {
            if ($price < (float)$product->last_border_price) {
                return response()->json([
                    'error' => "Price violation: Pre-order price (৳" . number_format($price, 2) . ") cannot be below minimum border floor price (৳" . number_format($product->last_border_price, 2) . ")."
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $serialNumber = $request->serial_number ? trim($request->serial_number) : null;
            $initialStatus = 'pending';

            // 1. Concurrency Booking Guard with lockForUpdate()
            if ($serialNumber) {
                $lockedSerial = ProductSerial::where('serial_number', $serialNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedSerial) {
                    DB::rollBack();
                    return response()->json(['error' => "Serial '{$serialNumber}' does not exist in inventory."], 422);
                }

                if ($lockedSerial->status !== 'available') {
                    DB::rollBack();
                    return response()->json(['error' => "Serial '{$serialNumber}' is currently '{$lockedSerial->status}' and cannot be booked (must be 'available')."], 422);
                }

                if ($lockedSerial->warehouse_id != $request->from_warehouse_id) {
                    DB::rollBack();
                    return response()->json(['error' => "Serial '{$serialNumber}' belongs to another branch, not the selected source branch."], 422);
                }

                // Reserve serial at Source Branch:
                // warehouse_id stays as from_warehouse_id!
                $lockedSerial->status = 'reserved';
                $lockedSerial->save();

                // Deduct from source salable stock
                $product->syncSerialStock($request->from_warehouse_id);
                $initialStatus = 'confirmed';
            }

            // Customer Resolution
            $customer = Customer::where('phone_number', $request->customer_phone)->first();
            if (!$customer) {
                $customer = Customer::create([
                    'customer_group_id' => 1,
                    'name' => $request->customer_name,
                    'phone_number' => $request->customer_phone,
                    'is_active' => true,
                ]);
            }

            $userId = Auth::id();
            $cashRegister = CashRegister::where('user_id', $userId)
                ->where('warehouse_id', $request->to_warehouse_id)
                ->where('status', true)
                ->first();

            $account = Account::where('is_default', true)->first() ?? Account::first();
            $accountId = $account ? $account->id : 1;

            $orderNo = 'PO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $preOrder = PreOrder::create([
                'order_no' => $orderNo,
                'customer_id' => $customer->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'product_id' => $request->product_id,
                'serial_number' => $serialNumber,
                'price' => $price,
                'advance_amount' => $advance,
                'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                'account_id' => $accountId,
                'paying_method' => $request->input('paying_method', 'Cash'),
                'status' => $initialStatus,
                'user_id' => $userId,
                'expected_delivery_date' => $request->expected_delivery_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Pre-Order #{$orderNo} booked successfully!",
                'pre_order_id' => $preOrder->id,
                'order_no' => $orderNo,
                'status' => $initialStatus,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Pre-order booking failed: ' . $e->getMessage()], 422);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,processing,ready,cancelled',
            'serial_number' => 'nullable|string',
        ]);

        $preOrder = PreOrder::findOrFail($id);
        $newStatus = $request->status;
        $product = Product::find($preOrder->product_id);

        DB::beginTransaction();
        try {
            if ($newStatus === 'confirmed') {
                $sn = $request->serial_number ? trim($request->serial_number) : $preOrder->serial_number;
                if (!$sn) {
                    DB::rollBack();
                    return response()->json(['error' => 'A serial number must be assigned to confirm the order.'], 422);
                }

                $serial = ProductSerial::where('serial_number', $sn)->lockForUpdate()->first();
                if (!$serial || ($serial->status !== 'available' && $serial->status !== 'reserved')) {
                    DB::rollBack();
                    return response()->json(['error' => "Serial '{$sn}' is not available."], 422);
                }

                $serial->status = 'reserved';
                $serial->save();
                $preOrder->serial_number = $sn;
                $preOrder->status = 'confirmed';
                $product->syncSerialStock($preOrder->from_warehouse_id);
            }
            elseif ($newStatus === 'processing') {
                // Sourced branch dispatches laptop on courier/vehicle
                if ($preOrder->serial_number) {
                    $serial = ProductSerial::where('serial_number', $preOrder->serial_number)->lockForUpdate()->first();
                    if ($serial) {
                        $serial->status = 'transferring';
                        $serial->save();
                    }
                }
                $preOrder->status = 'processing';
            }
            elseif ($newStatus === 'ready') {
                // =========================================================================
                // THE DETERMINISTIC HANDSHAKE MOMENT!
                // Laptop physically arrives at Destination Branch.
                // warehouse_id officially moves from source to destination!
                // =========================================================================
                if (!$preOrder->serial_number) {
                    DB::rollBack();
                    return response()->json(['error' => 'Cannot mark ready without an assigned serial number.'], 422);
                }

                $serial = ProductSerial::where('serial_number', $preOrder->serial_number)->lockForUpdate()->first();
                if (!$serial) {
                    DB::rollBack();
                    return response()->json(['error' => 'Assigned serial not found in inventory records.'], 422);
                }

                $sourceWhId = $serial->warehouse_id;
                $destWhId = $preOrder->to_warehouse_id;

                // Move physical & digital warehouse to destination
                $serial->warehouse_id = $destWhId;
                $serial->status = 'reserved'; // Reserved for this customer at destination branch
                $serial->save();

                // Synchronize stock for both branches
                $product->syncSerialStock($sourceWhId);
                $product->syncSerialStock($destWhId);

                $preOrder->status = 'ready';
            }
            elseif ($newStatus === 'cancelled') {
                // Free up serial back to available stock at source
                if ($preOrder->serial_number) {
                    $serial = ProductSerial::where('serial_number', $preOrder->serial_number)->lockForUpdate()->first();
                    if ($serial) {
                        $serial->warehouse_id = $preOrder->from_warehouse_id;
                        $serial->status = 'available';
                        $serial->save();
                        $product->syncSerialStock($preOrder->from_warehouse_id);
                    }
                }
                $preOrder->status = 'cancelled';
            }

            $preOrder->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Pre-Order #{$preOrder->order_no} status updated to " . strtoupper($newStatus),
                'new_status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Status update failed: ' . $e->getMessage()], 422);
        }
    }

    public function convertToSale(Request $request, $id)
    {
        $preOrder = PreOrder::findOrFail($id);

        if ($preOrder->status !== 'ready') {
            return response()->json([
                'error' => "Pre-order must be in 'ready' status to complete delivery (current status: {$preOrder->status})."
            ], 422);
        }

        if (!$preOrder->serial_number) {
            return response()->json(['error' => 'No serial number associated with this pre-order.'], 422);
        }

        $userId = Auth::id();
        $warehouseId = $preOrder->to_warehouse_id;
        $product = Product::find($preOrder->product_id);
        $totalPrice = (float)$preOrder->price;
        $advance = (float)$preOrder->advance_amount;
        $due = round($totalPrice - $advance, 2);

        DB::beginTransaction();
        try {
            // Lock serial at destination branch
            $serial = ProductSerial::where('serial_number', $preOrder->serial_number)
                ->lockForUpdate()
                ->first();

            if (!$serial) {
                DB::rollBack();
                return response()->json(['error' => 'Serial number not found.'], 422);
            }

            if ($serial->status !== 'ready' && $serial->status !== 'reserved') {
                DB::rollBack();
                return response()->json(['error' => "Serial status is '{$serial->status}', cannot convert to sale."], 422);
            }

            $cashRegister = CashRegister::where('user_id', $userId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', true)
                ->first();

            $saleRef = 'po-sale-' . date("Ymd") . '-' . date("his");

            // 1. Create Sale Record
            $sale = Sale::create([
                'reference_no' => $saleRef,
                'user_id' => $userId,
                'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                'customer_id' => $preOrder->customer_id,
                'warehouse_id' => $warehouseId,
                'biller_id' => 1,
                'currency_id' => 1,
                'exchange_rate' => 1,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_price' => $totalPrice,
                'order_tax_rate' => 0,
                'order_tax' => 0,
                'order_discount' => 0,
                'shipping_cost' => 0,
                'grand_total' => $totalPrice,
                'sale_status' => 1,
                'payment_status' => 4, // Paid
                'paid_amount' => $totalPrice,
                'sale_note' => "Delivered from Pre-Order #{$preOrder->order_no}",
            ]);

            Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'qty' => 1,
                'sale_unit_id' => 1,
                'net_unit_price' => $totalPrice,
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => 0,
                'total' => $totalPrice,
                'imei_number' => $serial->serial_number,
            ]);

            // 2. Mark Serial as SOLD
            $serial->markAsSold($sale->id);

            // 3. SPLIT SETTLEMENT PAYMENTS (Zero Double-Counting)
            $account = Account::where('is_default', true)->first() ?? Account::first();
            $accountId = $account ? $account->id : 1;

            if ($advance > 0) {
                // Settle previously recorded advance deposit
                Payment::create([
                    'payment_reference' => 'po-adv-' . date("Ymd") . '-' . date("his"),
                    'user_id' => $userId,
                    'sale_id' => $sale->id,
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'account_id' => $accountId,
                    'amount' => $advance,
                    'change' => 0,
                    'paying_method' => 'Advance Deposit',
                    'payment_note' => "Pre-Order #{$preOrder->order_no} advance deposit adjusted",
                    'payment_at' => date('Y-m-d H:i:s'),
                ]);
            }

            if ($due > 0) {
                // Final balance payment collected today at delivery
                Payment::create([
                    'payment_reference' => 'po-fin-' . date("Ymd") . '-' . date("his"),
                    'user_id' => $userId,
                    'sale_id' => $sale->id,
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'account_id' => $accountId,
                    'amount' => $due,
                    'change' => 0,
                    'paying_method' => $request->input('paying_method', 'Cash'),
                    'payment_note' => "Pre-Order #{$preOrder->order_no} remaining balance collected at delivery",
                    'payment_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Update PreOrder
            $preOrder->status = 'delivered';
            $preOrder->sale_id = $sale->id;
            $preOrder->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Pre-Order #{$preOrder->order_no} successfully delivered and converted to Sale #{$saleRef}!",
                'sale_id' => $sale->id,
                'sale_ref' => $saleRef,
                'invoice_url' => url("sales/gen_invoice/{$sale->id}"),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Conversion to sale failed: ' . $e->getMessage()], 422);
        }
    }
}
