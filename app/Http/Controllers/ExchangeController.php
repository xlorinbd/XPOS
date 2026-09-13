<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Account;
use App\Models\CashRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExchangeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
    }

    public function create(Request $request)
    {
        $serialNumber = trim($request->input('serial_number', ''));
        $oldSerial = null;
        $originalSale = null;
        $originalProductSale = null;
        $tradeInValue = 0;

        if ($serialNumber) {
            $oldSerial = ProductSerial::with(['product', 'warehouse'])
                ->where('serial_number', $serialNumber)
                ->first();

            if ($oldSerial && $oldSerial->sale_id) {
                $originalSale = Sale::with('customer')->find($oldSerial->sale_id);
                $originalProductSale = Product_Sale::where('sale_id', $originalSale->id)
                    ->where('product_id', $oldSerial->product_id)
                    ->first();

                $tradeInValue = $originalProductSale ? $originalProductSale->net_unit_price : ($oldSerial->product->price ?? 0);
            }
        }

        $warehouses = Warehouse::where('is_active', true)->get();
        $accounts = Account::where('is_active', true)->get();

        return view('backend.return.exchange_create', compact(
            'oldSerial',
            'originalSale',
            'originalProductSale',
            'tradeInValue',
            'warehouses',
            'accounts'
        ));
    }

    public function getAvailableSerials(Request $request)
    {
        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');

        if (!$productId || !$warehouseId) {
            return response()->json([]);
        }

        $serials = ProductSerial::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->select('id', 'serial_number', 'detailed_condition')
            ->get();

        return response()->json($serials);
    }

    public function store(Request $request)
    {
        $request->validate([
            'old_serial_number' => 'required|string',
            'return_action' => 'required|in:damaged,restock',
            'new_product_id' => 'required|integer',
            'new_serial_number' => 'required|string',
            'trade_in_value' => 'required|numeric|min:0',
            'new_price' => 'required|numeric|min:0',
            'warehouse_id' => 'required|integer',
        ]);

        $warehouseId = $request->warehouse_id;
        $tradeInValue = (float)$request->trade_in_value;
        $newPrice = (float)$request->new_price;
        $difference = round($newPrice - $tradeInValue, 2);

        // 1. Price Floor Validation on New Product (Zero Loophole)
        $newProduct = Product::find($request->new_product_id);
        if (!$newProduct) {
            return response()->json(['error' => 'Selected replacement product not found.'], 422);
        }

        if ($newProduct->last_border_price && (float)$newProduct->last_border_price > 0) {
            if ($newPrice < (float)$newProduct->last_border_price) {
                return response()->json([
                    'error' => "Price violation: Replacement price (৳" . number_format($newPrice, 2) . ") cannot be below minimum border floor price (৳" . number_format($newProduct->last_border_price, 2) . ")."
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // 2. Strict Binary Ownership & Idempotency on Old Serial
            $lockedOldSerial = ProductSerial::where('serial_number', $request->old_serial_number)
                ->lockForUpdate()
                ->first();

            if (!$lockedOldSerial) {
                DB::rollBack();
                return response()->json(['error' => "Old serial '{$request->old_serial_number}' does not exist."], 422);
            }

            if ($lockedOldSerial->status !== 'sold') {
                DB::rollBack();
                return response()->json(['error' => "Old serial '{$request->old_serial_number}' is {$lockedOldSerial->status} and cannot be returned (must be 'sold')."], 422);
            }

            $origSale = Sale::find($lockedOldSerial->sale_id);
            if (!$origSale) {
                DB::rollBack();
                return response()->json(['error' => "Original sale record not found for old serial."], 422);
            }

            // 3. Concurrency Lock & Availability on New Serial
            $lockedNewSerial = ProductSerial::where('serial_number', $request->new_serial_number)
                ->lockForUpdate()
                ->first();

            if (!$lockedNewSerial) {
                DB::rollBack();
                return response()->json(['error' => "New serial '{$request->new_serial_number}' does not exist in inventory."], 422);
            }

            if ($lockedNewSerial->status !== 'available') {
                DB::rollBack();
                return response()->json(['error' => "New serial '{$request->new_serial_number}' is {$lockedNewSerial->status} and not available for exchange."], 422);
            }

            $userId = Auth::id();
            $customerId = $origSale->customer_id;
            $billerId = $origSale->biller_id;
            $account = Account::where('is_default', true)->first() ?? Account::first();
            $accountId = $account ? $account->id : 1;

            $origProductSale = Product_Sale::where('sale_id', $origSale->id)
                ->where('product_id', $lockedOldSerial->product_id)
                ->first();

            $expectedTradeIn = $origProductSale ? (float)$origProductSale->net_unit_price : (float)($lockedOldSerial->product->price ?? 0);

            $isOverridden = false;
            if (abs($expectedTradeIn - $tradeInValue) > 0.01) {
                if (Auth::user()->role_id > 2) {
                    DB::rollBack();
                    return response()->json(['error' => 'Manager permission required to override trade-in credit value.'], 403);
                }
                $isOverridden = true;
            }

            // 4. Create Return Record for Old Device
            $returnRef = 'ex-ret-' . date("Ymd") . '-' . date("his");
            $returnRecord = Returns::create([
                'reference_no' => $returnRef,
                'user_id' => $userId,
                'sale_id' => $origSale->id,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'biller_id' => $billerId,
                'account_id' => $accountId,
                'currency_id' => $origSale->currency_id ?? 1,
                'exchange_rate' => $origSale->exchange_rate ?? 1,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_price' => $tradeInValue,
                'order_tax_rate' => 0,
                'order_tax' => 0,
                'grand_total' => $tradeInValue,
                'return_note' => "Exchange swap for new serial: {$lockedNewSerial->serial_number}" . ($isOverridden ? " [Manager Override Trade-in: ৳{$tradeInValue}]" : ""),
            ]);

            ProductReturn::create([
                'return_id' => $returnRecord->id,
                'product_id' => $lockedOldSerial->product_id,
                'imei_number' => $lockedOldSerial->serial_number,
                'qty' => 1,
                'sale_unit_id' => 1,
                'net_unit_price' => $tradeInValue,
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => 0,
                'total' => $tradeInValue,
            ]);

            // Transition Old Serial
            if ($request->return_action === 'damaged') {
                $lockedOldSerial->markAsDamaged($warehouseId);
            } else {
                $lockedOldSerial->markAsAvailable($warehouseId);
            }

            // 5. Create Sale Record for New Device
            $saleRef = 'ex-sale-' . date("Ymd") . '-' . date("his");
            $cashRegister = CashRegister::where('user_id', $userId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', true)
                ->first();

            $newSale = Sale::create([
                'reference_no' => $saleRef,
                'user_id' => $userId,
                'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'biller_id' => $billerId,
                'currency_id' => $origSale->currency_id ?? 1,
                'exchange_rate' => $origSale->exchange_rate ?? 1,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_price' => $newPrice,
                'order_tax_rate' => 0,
                'order_tax' => 0,
                'order_discount' => 0,
                'shipping_cost' => 0,
                'grand_total' => $newPrice,
                'sale_status' => 1,
                'payment_status' => ($difference <= 0) ? 4 : 4, // fully settled
                'paid_amount' => $newPrice,
                'sale_note' => "Exchange swap from old serial: {$lockedOldSerial->serial_number} (Return Ref: {$returnRef})",
            ]);

            Product_Sale::create([
                'sale_id' => $newSale->id,
                'product_id' => $newProduct->id,
                'qty' => 1,
                'sale_unit_id' => 1,
                'net_unit_price' => $newPrice,
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => 0,
                'total' => $newPrice,
                'imei_number' => $lockedNewSerial->serial_number,
            ]);

            // Transition New Serial
            $lockedNewSerial->markAsSold($newSale->id);

            // 6. Differential Settlement Payment
            $account = Account::where('is_default', true)->first();
            $accountId = $account ? $account->id : 1;

            if ($difference > 0) {
                // Customer pays extra difference
                Payment::create([
                    'payment_reference' => 'ex-pay-' . date("Ymd") . '-' . date("his"),
                    'user_id' => $userId,
                    'sale_id' => $newSale->id,
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'account_id' => $accountId,
                    'amount' => $difference,
                    'change' => 0,
                    'paying_method' => $request->input('paying_method', 'Cash'),
                    'payment_note' => "Exchange difference collected from customer (New: ৳{$newPrice} - Old: ৳{$tradeInValue})",
                    'payment_at' => date('Y-m-d H:i:s'),
                ]);
            } elseif ($difference < 0) {
                // Store refunds difference to customer
                Payment::create([
                    'payment_reference' => 'ex-ref-' . date("Ymd") . '-' . date("his"),
                    'user_id' => $userId,
                    'sale_id' => $newSale->id,
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'account_id' => $accountId,
                    'amount' => abs($difference),
                    'change' => 0,
                    'paying_method' => $request->input('paying_method', 'Cash'),
                    'payment_note' => "Exchange refund issued to customer (Old: ৳{$tradeInValue} - New: ৳{$newPrice})",
                    'payment_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                // ৳0 Even Warranty Replacement
                Payment::create([
                    'payment_reference' => 'ex-swap-' . date("Ymd") . '-' . date("his"),
                    'user_id' => $userId,
                    'sale_id' => $newSale->id,
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'account_id' => $accountId,
                    'amount' => 0,
                    'change' => 0,
                    'paying_method' => 'Warranty Replacement',
                    'payment_note' => "Even 1-to-1 Warranty Swap (৳0 difference)",
                    'payment_at' => date('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Exchange completed successfully! New Sale Reference: {$saleRef}",
                'new_sale_id' => $newSale->id,
                'return_ref' => $returnRef,
                'sale_ref' => $saleRef,
                'difference' => $difference,
                'invoice_url' => url("sales/gen_invoice/{$newSale->id}"),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Exchange failed: ' . $e->getMessage()], 422);
        }
    }
}
