<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class WarrantyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
    }

    public function index()
    {
        return view('backend.warranty.lookup');
    }

    public function lookup(Request $request)
    {
        $query = trim($request->input('query'));
        if (empty($query)) {
            return response()->json(['error' => 'Please enter a valid Serial Number or Sale Reference.'], 422);
        }

        $serial = ProductSerial::with(['product', 'warehouse'])
            ->where('serial_number', $query)
            ->first();

        $sale = null;
        if ($serial && $serial->sale_id) {
            $sale = Sale::with(['customer', 'warehouse'])->find($serial->sale_id);
        }

        if (!$sale) {
            $sale = Sale::with(['customer', 'warehouse'])->where('reference_no', $query)->first();
            if ($sale && !$serial) {
                $serial = ProductSerial::with(['product', 'warehouse'])
                    ->where('sale_id', $sale->id)
                    ->first();
            }
        }

        if (!$sale && !$serial) {
            return response()->json(['error' => "No warranty or sales record found matching '{$query}'."], 404);
        }

        $product = $serial ? $serial->product : null;
        if (!$product && $sale) {
            $ps = $sale->productSales()->first();
            if ($ps) {
                $product = Product::find($ps->product_id);
            }
        }

        if (!$product) {
            return response()->json(['error' => "Product details unavailable for '{$query}'."], 404);
        }

        $saleDate = $sale ? Carbon::parse($sale->created_at) : null;
        $now = Carbon::now();

        // 1. Guarantee Calculation (e.g. Replacement window)
        $guaranteeDays = 0;
        if ($product->guarantee) {
            if ($product->guarantee_type === 'months') {
                $guaranteeDays = (int)$product->guarantee * 30;
            } elseif ($product->guarantee_type === 'years') {
                $guaranteeDays = (int)$product->guarantee * 365;
            } else {
                $guaranteeDays = (int)$product->guarantee; // default days
            }
        }
        $guaranteeExpire = ($saleDate && $guaranteeDays > 0) ? $saleDate->copy()->addDays($guaranteeDays) : null;
        $guaranteeStatus = 'N/A';
        $guaranteeRemainingDays = 0;
        if ($guaranteeExpire) {
            if ($now->lessThanOrEqualTo($guaranteeExpire)) {
                $guaranteeStatus = 'Active';
                $guaranteeRemainingDays = (int)$now->diffInDays($guaranteeExpire, false);
            } else {
                $guaranteeStatus = 'Expired';
                $guaranteeRemainingDays = 0;
            }
        }

        // 2. Warranty Calculation (e.g. Free Service window)
        $warrantyDays = 0;
        if ($product->warranty) {
            if ($product->warranty_type === 'years') {
                $warrantyDays = (int)$product->warranty * 365;
            } elseif ($product->warranty_type === 'days') {
                $warrantyDays = (int)$product->warranty;
            } else {
                $warrantyDays = (int)$product->warranty * 30; // default months
            }
        }
        $warrantyExpire = ($saleDate && $warrantyDays > 0) ? $saleDate->copy()->addDays($warrantyDays) : null;
        $warrantyStatus = 'N/A';
        $warrantyRemainingDays = 0;
        if ($warrantyExpire) {
            if ($now->lessThanOrEqualTo($warrantyExpire)) {
                $warrantyStatus = 'Active';
                $warrantyRemainingDays = (int)$now->diffInDays($warrantyExpire, false);
            } else {
                $warrantyStatus = 'Expired';
                $warrantyRemainingDays = 0;
            }
        }

        // Mask customer phone for security/privacy
        $customerPhone = $sale && $sale->customer ? ($sale->customer->phone_number ?? '') : '';
        $maskedPhone = '';
        if ($customerPhone) {
            $len = strlen($customerPhone);
            if ($len > 6) {
                $maskedPhone = substr($customerPhone, 0, 3) . str_repeat('*', $len - 6) . substr($customerPhone, -3);
            } else {
                $maskedPhone = '***';
            }
        }

        $specsParts = array_filter([
            $product->processor,
            $product->ram,
            $product->storage,
            $product->display,
            $product->dedicated_graphics
        ]);
        $specsLine = implode(' | ', $specsParts);

        return response()->json([
            'success' => true,
            'serial_number' => $serial ? $serial->serial_number : 'N/A',
            'serial_status' => $serial ? $serial->status : 'N/A',
            'detailed_condition' => $serial ? ($serial->detailed_condition ?? 'Standard') : 'N/A',
            'product_name' => $product->name,
            'product_code' => $product->code,
            'specs_line' => $specsLine ?: 'Standard Specs',
            'product_condition' => $product->product_condition ? strtoupper($product->product_condition) : 'N/A',
            'sale_reference' => $sale ? $sale->reference_no : 'N/A',
            'sale_date' => $saleDate ? $saleDate->format('d M Y, h:i A') : 'N/A',
            'branch' => $sale && $sale->warehouse ? $sale->warehouse->name : ($serial && $serial->warehouse ? $serial->warehouse->name : 'N/A'),
            'customer_name' => $sale && $sale->customer ? $sale->customer->name : 'Walk-in Customer',
            'customer_phone' => $maskedPhone,
            'guarantee' => [
                'duration' => $product->guarantee ? ($product->guarantee . ' ' . $product->guarantee_type) : 'None',
                'status' => $guaranteeStatus,
                'expire_date' => $guaranteeExpire ? $guaranteeExpire->format('d M Y') : 'N/A',
                'remaining_days' => $guaranteeRemainingDays,
            ],
            'warranty' => [
                'duration' => $product->warranty ? ($product->warranty . ' ' . $product->warranty_type) : 'None',
                'status' => $warrantyStatus,
                'expire_date' => $warrantyExpire ? $warrantyExpire->format('d M Y') : 'N/A',
                'remaining_days' => $warrantyRemainingDays,
            ],
            'return_url' => $sale ? (url('return-sale/create') . '?reference_no=' . $sale->reference_no) : '#',
            'exchange_url' => $serial ? (url('exchange/create') . '?serial_number=' . urlencode($serial->serial_number)) : '#',
        ]);
    }
}
