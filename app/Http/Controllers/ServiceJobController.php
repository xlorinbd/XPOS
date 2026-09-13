<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceJob;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Payment;
use App\Models\Account;
use App\Models\CashRegister;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceJobController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active']);
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $query = ServiceJob::with(['product', 'serial', 'customer', 'warehouse', 'technician'])
            ->latest();

        if ($status && in_array($status, ['received', 'sent_to_lab', 'under_service', 'ready_for_delivery', 'delivered', 'rejected', 'scrapped'])) {
            $query->where('status', $status);
        }

        $serviceJobs = $query->paginate(20);
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('backend.service_job.index', compact('serviceJobs', 'warehouses', 'status'));
    }

    public function create(Request $request)
    {
        $serialNumber = trim($request->input('serial_number', ''));
        $serial = null;
        $sale = null;
        $product = null;
        $isWarrantyActive = false;
        $warrantyDaysRemaining = 0;

        if ($serialNumber) {
            $serial = ProductSerial::where('serial_number', $serialNumber)->with(['product', 'warehouse'])->first();
            if ($serial && $serial->sale_id) {
                $sale = Sale::with('customer')->find($serial->sale_id);
            }
            if ($serial) {
                $product = $serial->product;
            }
        }

        if ($product && $sale) {
            $saleDate = Carbon::parse($sale->created_at);
            $warrantyDays = 0;
            if ($product->warranty) {
                if ($product->warranty_type === 'years') $warrantyDays = (int)$product->warranty * 365;
                elseif ($product->warranty_type === 'days') $warrantyDays = (int)$product->warranty;
                else $warrantyDays = (int)$product->warranty * 30;
            }

            if ($warrantyDays > 0) {
                $warrantyExpire = $saleDate->copy()->addDays($warrantyDays);
                $now = Carbon::now();
                if ($now->lessThanOrEqualTo($warrantyExpire)) {
                    $isWarrantyActive = true;
                    $warrantyDaysRemaining = (int)$now->diffInDays($warrantyExpire, false);
                }
            }
        }

        $warehouses = Warehouse::where('is_active', true)->get();

        return view('backend.service_job.create', compact(
            'serial',
            'sale',
            'product',
            'isWarrantyActive',
            'warrantyDaysRemaining',
            'warehouses'
        ));
    }

    public function show($id)
    {
        $serviceJob = ServiceJob::with(['product', 'serial', 'customer', 'warehouse', 'technician'])->findOrFail($id);
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($serviceJob);
        }
        return view('backend.service_job.show', compact('serviceJob'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string',
            'customer_name' => 'required|string|max:191',
            'customer_phone' => 'required|string|max:191',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'problem_description' => 'required|string',
        ]);

        $sn = trim($request->serial_number);

        DB::beginTransaction();
        try {
            // 1. Concurrency Guard: Row Lock on Serial
            $lockedSerial = ProductSerial::where('serial_number', $sn)
                ->lockForUpdate()
                ->first();

            if (!$lockedSerial) {
                DB::rollBack();
                return response()->json(['error' => "Serial '{$sn}' does not exist in inventory records."], 422);
            }

            // Must be strictly 'sold' to be received for service
            if ($lockedSerial->status !== 'sold') {
                DB::rollBack();
                return response()->json([
                    'error' => "Serial '{$sn}' cannot be taken for service because it is currently '{$lockedSerial->status}' (must be 'sold')."
                ], 422);
            }

            // 2. Prevent Duplicate Active Service Tickets
            $existingJob = ServiceJob::where('serial_number', $sn)->active()->first();
            if ($existingJob) {
                DB::rollBack();
                return response()->json([
                    'error' => "An active service job (#{$existingJob->ticket_no}) is already open for serial '{$sn}'."
                ], 422);
            }

            $product = Product::find($lockedSerial->product_id);
            $saleId = $lockedSerial->sale_id;

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

            // 3. Concurrency-Safe Unique Ticket Generation with Retry Loop
            $ticketNo = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = 'SRV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                if (!ServiceJob::where('ticket_no', $candidate)->exists()) {
                    $ticketNo = $candidate;
                    break;
                }
            }

            if (!$ticketNo) {
                $ticketNo = 'SRV-' . date('Ymd') . '-' . time();
            }

            // Check Warranty Coverage
            $isWarrantyCovered = $request->boolean('is_warranty_covered', true);

            // 4. Transition Serial to 'under_service'
            $lockedSerial->status = 'under_service';
            $lockedSerial->save();

            // Create Service Job Record
            $serviceJob = ServiceJob::create([
                'ticket_no' => $ticketNo,
                'product_serial_id' => $lockedSerial->id,
                'serial_number' => $sn,
                'product_id' => $product->id,
                'sale_id' => $saleId,
                'customer_id' => $customer->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'warehouse_id' => $request->warehouse_id,
                'is_warranty_covered' => $isWarrantyCovered,
                'problem_description' => $request->problem_description,
                'condition_notes' => $request->condition_notes,
                'accessories_received' => $request->accessories_received,
                'status' => 'received',
                'service_charge' => 0,
                'parts_charge' => 0,
                'total_cost' => 0,
                'received_at' => Carbon::now(),
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Service ticket #{$ticketNo} generated successfully!",
                'ticket_id' => $serviceJob->id,
                'ticket_no' => $ticketNo,
                'print_url' => route('service_jobs.print', $serviceJob->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Service intake failed: ' . $e->getMessage()], 422);
        }
    }

    public function printToken($id)
    {
        $job = ServiceJob::with(['product', 'serial', 'customer', 'warehouse', 'technician'])->findOrFail($id);
        return view('backend.service_job.token', compact('job'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:sent_to_lab,under_service,ready_for_delivery,rejected,scrapped',
            'technician_notes' => 'nullable|string',
            'parts_charge' => 'nullable|numeric|min:0',
            'service_charge' => 'nullable|numeric|min:0',
        ]);

        $job = ServiceJob::findOrFail($id);
        $newStatus = $request->status;

        DB::beginTransaction();
        try {
            $lockedSerial = ProductSerial::where('id', $job->product_serial_id)->lockForUpdate()->first();

            if ($request->filled('technician_notes')) {
                $job->technician_notes = $request->technician_notes;
            }

            if ($request->has('parts_charge')) {
                $job->parts_charge = (float)$request->parts_charge;
            }
            if ($request->has('service_charge')) {
                $job->service_charge = (float)$request->service_charge;
            }
            $job->total_cost = round((float)$job->service_charge + (float)$job->parts_charge, 2);

            if ($newStatus === 'ready_for_delivery') {
                $job->completed_at = Carbon::now();
                $job->status = 'ready_for_delivery';
            } elseif ($newStatus === 'rejected') {
                // Warranty voided / unrepairable - device returned to customer as-is
                $job->status = 'rejected';
                $job->completed_at = Carbon::now();
                if ($lockedSerial) {
                    $lockedSerial->status = 'sold'; // back with customer
                    $lockedSerial->save();
                }
            } elseif ($newStatus === 'scrapped') {
                // Completely dead / abandoned by customer -> marked damaged
                $job->status = 'scrapped';
                $job->completed_at = Carbon::now();
                if ($lockedSerial) {
                    $lockedSerial->status = 'damaged';
                    $lockedSerial->save();
                }
            } else {
                $job->status = $newStatus;
            }

            $job->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Service ticket #{$job->ticket_no} updated to " . strtoupper($newStatus),
                'new_status' => $newStatus,
                'total_cost' => $job->total_cost,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Status update failed: ' . $e->getMessage()], 422);
        }
    }

    public function deliver(Request $request, $id)
    {
        $job = ServiceJob::findOrFail($id);

        if (!in_array($job->status, ['ready_for_delivery', 'under_service'])) {
            return response()->json([
                'error' => "Ticket must be 'ready_for_delivery' to deliver device (current: {$job->status})."
            ], 422);
        }

        DB::beginTransaction();
        try {
            $totalCost = (float)$job->total_cost;
            $payingMethod = $request->input('paying_method', 'Cash');

            // 1. If billable repair, record Payment
            if ($totalCost > 0) {
                $account = Account::where('is_default', true)->first() ?? Account::first();
                $cashRegister = CashRegister::where('user_id', Auth::id())
                    ->where('warehouse_id', $job->warehouse_id)
                    ->where('status', true)
                    ->first();

                Payment::create([
                    'payment_reference' => 'srv-pay-' . date("Ymd") . '-' . date("his"),
                    'user_id' => Auth::id(),
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'account_id' => $account ? $account->id : 1,
                    'amount' => $totalCost,
                    'change' => 0,
                    'paying_method' => $payingMethod,
                    'payment_note' => "Service ticket #{$job->ticket_no} payment (Labor: ৳{$job->service_charge} + Parts: ৳{$job->parts_charge})",
                    'payment_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // 2. Transition Serial back to 'sold'
            $lockedSerial = ProductSerial::where('id', $job->product_serial_id)->lockForUpdate()->first();
            if ($lockedSerial) {
                $lockedSerial->status = 'sold';
                $lockedSerial->save();
            }

            $job->status = 'delivered';
            $job->paid_amount = $totalCost;
            $job->paying_method = $payingMethod;
            $job->delivered_at = Carbon::now();
            $job->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Service ticket #{$job->ticket_no} delivered successfully to customer!",
                'status' => 'delivered',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Delivery failed: ' . $e->getMessage()], 422);
        }
    }
}
