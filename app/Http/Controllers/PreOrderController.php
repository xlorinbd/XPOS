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
use App\Services\KgNotifier;

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


    // ------------------------------------------------------------------
    // who may do what
    // ------------------------------------------------------------------
    private function isGlobal(): bool
    {
        return (int) Auth::user()->role_id <= 2;
    }

    /** Admin, Manager and Branch Manager take the decisions (confirm, dispatch, cancel, refund). */
    private function isDecisionMaker(): bool
    {
        return (int) Auth::user()->role_id <= 3;
    }

    private function worksIn(?int $warehouseId): bool
    {
        if ($this->isGlobal()) {
            return true;
        }
        $user = Auth::user();
        $ids = $user->branches()->pluck('warehouses.id')->map(fn($i) => (int) $i)->all();
        if ($user->warehouse_id) {
            $ids[] = (int) $user->warehouse_id;
        }
        return $warehouseId !== null && in_array((int) $warehouseId, $ids, true);
    }

    private function deny(string $message = 'You are not allowed to do this for this branch.')
    {
        return response()->json(['error' => $message], 403);
    }

    private function poUrl(PreOrder $po): string
    {
        return route('pre_orders.show', $po->id, false);
    }

    private function describe(PreOrder $po): string
    {
        $po->loadMissing(['product', 'fromWarehouse', 'toWarehouse']);
        $item = ($po->product->name ?? 'Product') . ($po->serial_number ? ' (' . $po->serial_number . ')' : '');
        return $po->order_no . ' - ' . $item;
    }

    // ------------------------------------------------------------------
    // booking
    // ------------------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:191',
            'customer_phone' => 'required|string|max:191',
            'product_id' => 'required|integer|exists:products,id',
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id',
            'price' => 'required|numeric|min:0',
            'advance_amount' => 'required|numeric|min:1',
            'paying_method' => 'required|string|max:60',
            'advance_account_id' => 'nullable|integer|exists:accounts,id',
            'serial_number' => 'nullable|string',
        ], [
            'advance_amount.min' => 'An advance payment is required to take a pre-order.',
            'advance_amount.required' => 'An advance payment is required to take a pre-order.',
        ]);

        $price = (float) $request->price;
        $advance = (float) $request->advance_amount;
        $product = Product::find($request->product_id);
        $fromId = (int) $request->from_warehouse_id;
        $toId = (int) $request->to_warehouse_id;
        $kind = $fromId === $toId ? 'pre_booked' : 'pre_order';

        if (!$this->worksIn($toId)) {
            return $this->deny('You can only take orders for your own branch.');
        }
        if (!$product->is_imei) {
            return response()->json(['error' => 'Pre-orders and pre-bookings are for serial-tracked items (laptops, phones).'], 422);
        }
        if ($advance > $price) {
            return response()->json(['error' => 'The advance cannot be more than the agreed price.'], 422);
        }

        // Border price: below it needs a reason (warning, not a block)
        [$borderReason, $borderResponse] = \App\Services\BorderPrice::check([$request->product_id], [$price], $request->border_price_reason);
        if ($borderResponse) {
            return $borderResponse;
        }

        $account = $request->advance_account_id
            ? Account::find($request->advance_account_id)
            : Account::defaultFor($request->paying_method, $toId);
        if (!$account || !$account->usableAt($toId)) {
            return response()->json(['error' => 'The selected account cannot be used for this branch.'], 422);
        }

        DB::beginTransaction();
        try {
            $serialNumber = $request->serial_number ? trim($request->serial_number) : null;
            $lockedSerial = null;

            if ($serialNumber) {
                $lockedSerial = ProductSerial::where('serial_number', $serialNumber)->lockForUpdate()->first();
                if (!$lockedSerial) {
                    DB::rollBack();
                    return response()->json(['error' => "Serial '{$serialNumber}' does not exist in inventory."], 422);
                }
            } else {
                // only stock that is really in a branch / warehouse counts; shipments still in transit have no available serials yet
                $lockedSerial = ProductSerial::where('product_id', $product->id)->where('warehouse_id', $fromId)
                    ->where('status', 'available')->orderBy('id')->lockForUpdate()->first();
                if (!$lockedSerial) {
                    DB::rollBack();
                    return response()->json(['error' => 'No unit of this product is available at the selected source branch right now (items still in transit cannot be pre-ordered).'], 422);
                }
                // a pre-order keeps the unit open until the source confirms; a pre-booking sets one aside now
                $lockedSerial = $kind === 'pre_booked' ? $lockedSerial : null;
            }

            if ($lockedSerial) {
                if ((int) $lockedSerial->product_id !== (int) $product->id) {
                    DB::rollBack();
                    return response()->json(['error' => 'That serial belongs to a different product.'], 422);
                }
                if ($lockedSerial->status !== 'available') {
                    DB::rollBack();
                    return response()->json(['error' => "Serial '{$lockedSerial->serial_number}' is currently '{$lockedSerial->status}' and cannot be booked (must be 'available')."], 422);
                }
                if ((int) $lockedSerial->warehouse_id !== $fromId) {
                    DB::rollBack();
                    return response()->json(['error' => "Serial '{$lockedSerial->serial_number}' belongs to another branch, not the selected source branch."], 422);
                }
                $lockedSerial->status = 'reserved';
                $lockedSerial->save();
                $product->syncSerialStock($fromId);
                $serialNumber = $lockedSerial->serial_number;
            }

            // a pre-booking is already at the branch (ready for pickup); a pre-order with a unit waits for the source to send it
            $initialStatus = $kind === 'pre_booked' ? 'ready' : ($serialNumber ? 'confirmed' : 'pending');

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
            $cashRegister = CashRegister::where('user_id', $userId)->where('warehouse_id', $toId)->where('status', true)->first();

            do {
                $orderNo = 'PO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } while (PreOrder::withTrashed()->where('order_no', $orderNo)->exists());

            $preOrder = PreOrder::create([
                'order_no' => $orderNo,
                'kind' => $kind,
                'customer_id' => $customer->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'from_warehouse_id' => $fromId,
                'to_warehouse_id' => $toId,
                'product_id' => $request->product_id,
                'serial_number' => $serialNumber,
                'price' => $price,
                'advance_amount' => $advance,
                'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                'account_id' => $account->id,
                'paying_method' => $request->paying_method,
                'status' => $initialStatus,
                'user_id' => $userId,
                'expected_delivery_date' => $request->expected_delivery_date,
                'notes' => $request->notes,
                'border_price_reason' => $borderReason,
            ]);

            // The advance is real money taken today: it goes into the chosen account right away.
            Payment::create([
                'payment_reference' => 'po-adv-' . date('Ymd') . '-' . $preOrder->id,
                'user_id' => $userId,
                'pre_order_id' => $preOrder->id,
                'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                'account_id' => $account->id,
                'amount' => $advance,
                'change' => 0,
                'paying_method' => $request->paying_method,
                'payment_note' => "Advance for {$orderNo}",
                'payment_at' => date('Y-m-d H:i:s'),
                'confirm_status' => $account->kind === 'gateway' ? 'pending' : 'confirmed',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Pre-order booking failed: ' . $e->getMessage()], 422);
        }

        $this->notifyBooked($preOrder);

        return response()->json([
            'success' => true,
            'message' => ($kind === 'pre_booked' ? 'Pre-Booking' : 'Pre-Order') . " #{$preOrder->order_no} saved.",
            'pre_order_id' => $preOrder->id,
            'order_no' => $preOrder->order_no,
            'status' => $preOrder->status,
        ]);
    }

    private function notifyBooked(PreOrder $po): void
    {
        $po->loadMissing(['product', 'fromWarehouse', 'toWarehouse']);
        $by = Auth::user()->name;
        $adv = amount_format($po->advance_amount);
        if ($po->kind === 'pre_booked') {
            KgNotifier::toRoles(['Branch Manager', 'Manager', 'Admin'],
                "Pre-Booked: {$this->describe($po)} kept aside at {$po->toWarehouse->name} for {$po->customer_name} (advance {$adv}, by {$by}).",
                $this->poUrl($po), 'pre_booked', $po->to_warehouse_id, Auth::id());
            return;
        }
        if ($po->status === 'confirmed') {
            $this->notifyConfirmed($po);
        } else {
            KgNotifier::toRoles(['Branch Manager', 'Seller', 'Manager', 'Admin'],
                "New Pre-Order {$this->describe($po)}: {$po->toWarehouse->name} needs a unit from {$po->fromWarehouse->name} (advance {$adv}, by {$by}). Please confirm.",
                $this->poUrl($po), 'pre_order', $po->from_warehouse_id, Auth::id());
        }
    }

    /** Confirm goes to three places at once: the source branch, the managers and the admin. */
    private function notifyConfirmed(PreOrder $po): void
    {
        $po->loadMissing(['product', 'fromWarehouse', 'toWarehouse']);
        KgNotifier::toRoles(['Branch Manager', 'Seller', 'Manager', 'Admin'],
            "Pre-Order confirmed: {$this->describe($po)}. Prepare it at {$po->fromWarehouse->name} to send to {$po->toWarehouse->name} (customer {$po->customer_name}). Managers can still change the source branch before it leaves.",
            $this->poUrl($po), 'pre_order', $po->from_warehouse_id, Auth::id());
    }

    // ------------------------------------------------------------------
    // status changes: confirm -> dispatch -> receive
    // ------------------------------------------------------------------
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,processing,ready',
            'serial_number' => 'nullable|string',
        ]);

        $preOrder = PreOrder::findOrFail($id);
        $newStatus = $request->status;
        $product = Product::find($preOrder->product_id);

        if ($preOrder->kind === 'pre_booked') {
            return response()->json(['error' => 'A pre-booking stays in its own branch; there is nothing to send or receive.'], 422);
        }
        if (in_array($newStatus, ['confirmed', 'processing'], true)) {
            if (!$this->isDecisionMaker() || !$this->worksIn($preOrder->from_warehouse_id)) {
                return $this->deny('Only the source branch manager (or Manager / Admin) can do this.');
            }
        } elseif (!$this->worksIn($preOrder->to_warehouse_id)) {
            return $this->deny('Only the receiving branch can mark this as received.');
        }

        $expected = ['confirmed' => 'pending', 'processing' => 'confirmed', 'ready' => 'processing'][$newStatus];
        if ($preOrder->status !== $expected) {
            return response()->json(['error' => "This pre-order is '{$preOrder->status}', so it cannot move to '{$newStatus}' now."], 422);
        }

        DB::beginTransaction();
        try {
            if ($newStatus === 'confirmed') {
                $sn = $request->serial_number ? trim($request->serial_number) : $preOrder->serial_number;
                $serial = $sn ? ProductSerial::where('serial_number', $sn)->lockForUpdate()->first() : null;
                if (!$serial) {
                    $serial = ProductSerial::where('product_id', $preOrder->product_id)->where('warehouse_id', $preOrder->from_warehouse_id)
                        ->where('status', 'available')->orderBy('id')->lockForUpdate()->first();
                }
                if (!$serial || (int) $serial->product_id !== (int) $preOrder->product_id
                    || (int) $serial->warehouse_id !== (int) $preOrder->from_warehouse_id
                    || !in_array($serial->status, ['available', 'reserved'], true)) {
                    DB::rollBack();
                    return response()->json(['error' => 'No available unit of this product at the source branch.'], 422);
                }
                $serial->status = 'reserved';
                $serial->save();
                $preOrder->serial_number = $serial->serial_number;
                $preOrder->status = 'confirmed';
                $product->syncSerialStock($preOrder->from_warehouse_id);
            } elseif ($newStatus === 'processing') {
                // the source branch sends the unit on courier / vehicle
                $serial = ProductSerial::where('serial_number', $preOrder->serial_number)->lockForUpdate()->first();
                if ($serial) {
                    $serial->status = 'transferring';
                    $serial->save();
                }
                $preOrder->status = 'processing';
            } else {
                // the unit physically reaches the branch that took the order
                $serial = $preOrder->serial_number ? ProductSerial::where('serial_number', $preOrder->serial_number)->lockForUpdate()->first() : null;
                if (!$serial) {
                    DB::rollBack();
                    return response()->json(['error' => 'Assigned serial not found in inventory records.'], 422);
                }
                $sourceWhId = $serial->warehouse_id;
                $serial->warehouse_id = $preOrder->to_warehouse_id;
                $serial->status = 'reserved';
                $serial->save();
                $product->syncSerialStock($sourceWhId);
                $product->syncSerialStock($preOrder->to_warehouse_id);
                $preOrder->status = 'ready';
            }

            $preOrder->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Status update failed: ' . $e->getMessage()], 422);
        }

        $by = Auth::user()->name;
        if ($newStatus === 'confirmed') {
            $this->notifyConfirmed($preOrder);
        } elseif ($newStatus === 'processing') {
            KgNotifier::toRoles(['Branch Manager', 'Seller', 'Manager'],
                "On the way: {$this->describe($preOrder)} left {$preOrder->fromWarehouse->name} for {$preOrder->toWarehouse->name} (sent by {$by}).",
                $this->poUrl($preOrder), 'pre_order', $preOrder->to_warehouse_id, Auth::id());
        } else {
            KgNotifier::toRoles(['Branch Manager', 'Seller', 'Manager'],
                "Ready for pickup: {$this->describe($preOrder)} reached {$preOrder->toWarehouse->name}. Call {$preOrder->customer_name} ({$preOrder->customer_phone}).",
                $this->poUrl($preOrder), 'pre_order', $preOrder->to_warehouse_id, Auth::id());
        }

        return response()->json([
            'success' => true,
            'message' => "Pre-Order #{$preOrder->order_no} is now " . strtoupper($preOrder->status === 'processing' ? 'in transit' : $preOrder->status),
            'new_status' => $preOrder->status,
        ]);
    }

    /** Manager / Admin can pick another branch as the source before the unit is sent. */
    public function changeSource(Request $request, $id)
    {
        $request->validate(['from_warehouse_id' => 'required|integer|exists:warehouses,id']);
        if (!$this->isGlobal()) {
            return $this->deny('Only a Manager or Admin can change the source branch.');
        }

        $po = PreOrder::findOrFail($id);
        $newFrom = (int) $request->from_warehouse_id;
        if ($po->kind === 'pre_booked' || !in_array($po->status, ['pending', 'confirmed'], true)) {
            return response()->json(['error' => 'The source branch can only be changed before the unit is sent.'], 422);
        }
        if ($newFrom === (int) $po->to_warehouse_id || $newFrom === (int) $po->from_warehouse_id) {
            return response()->json(['error' => 'Choose a different branch that is not the pickup branch.'], 422);
        }

        $product = Product::find($po->product_id);
        DB::beginTransaction();
        try {
            $oldFrom = (int) $po->from_warehouse_id;
            if ($po->serial_number) {
                $old = ProductSerial::where('serial_number', $po->serial_number)->lockForUpdate()->first();
                if ($old && $old->status === 'reserved') {
                    $old->status = 'available';
                    $old->save();
                }
                $product->syncSerialStock($oldFrom);
            }
            $available = ProductSerial::where('product_id', $po->product_id)->where('warehouse_id', $newFrom)->where('status', 'available')->orderBy('id')->lockForUpdate()->first();
            if (!$available) {
                DB::rollBack();
                return response()->json(['error' => 'That branch has no available unit of this product.'], 422);
            }
            $available->status = 'reserved';
            $available->save();
            $product->syncSerialStock($newFrom);

            $po->from_warehouse_id = $newFrom;
            $po->serial_number = $available->serial_number;
            $po->status = 'confirmed';
            $po->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Could not change the source branch: ' . $e->getMessage()], 422);
        }

        $po->load(['product', 'fromWarehouse', 'toWarehouse']);
        $oldName = Warehouse::find($oldFrom)->name ?? 'the previous branch';
        KgNotifier::toRoles(['Branch Manager', 'Seller', 'Manager', 'Admin'],
            "Source changed for {$this->describe($po)}: now {$po->fromWarehouse->name} instead of {$oldName}. Please prepare it at {$po->fromWarehouse->name}.",
            $this->poUrl($po), 'pre_order', $newFrom, Auth::id());
        KgNotifier::toRoles(['Branch Manager', 'Seller'],
            "{$po->order_no} will no longer be sent from {$oldName}; the reservation was released.",
            $this->poUrl($po), 'pre_order', $oldFrom, Auth::id());

        return response()->json(['success' => true, 'message' => "Source branch changed to {$po->fromWarehouse->name}."]);
    }

    // ------------------------------------------------------------------
    // cancel (Admin / Manager / Branch Manager decide how much advance goes back)
    // ------------------------------------------------------------------
    public function cancel(Request $request, $id)
    {
        $preOrder = PreOrder::findOrFail($id);
        $request->validate([
            'cancel_reason' => 'required|string|max:255',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_account_id' => 'nullable|integer|exists:accounts,id',
        ]);

        if (!$this->isDecisionMaker() || !($this->worksIn($preOrder->from_warehouse_id) || $this->worksIn($preOrder->to_warehouse_id))) {
            return $this->deny('Only a Branch Manager, Manager or Admin can cancel a pre-order.');
        }
        if (in_array($preOrder->status, ['delivered', 'cancelled'], true)) {
            return response()->json(['error' => 'This pre-order is already closed.'], 422);
        }

        $refund = round((float) ($request->refund_amount ?? 0), 2);
        $advance = (float) $preOrder->advance_amount;
        if ($refund > $advance) {
            return response()->json(['error' => 'The refund cannot be more than the advance (' . amount_format($advance) . ').'], 422);
        }
        $refundAccount = null;
        if ($refund > 0) {
            $refundAccount = $request->refund_account_id ? Account::find($request->refund_account_id) : Account::find($preOrder->account_id);
            if (!$refundAccount || !$refundAccount->usableAt($preOrder->to_warehouse_id)) {
                return response()->json(['error' => 'Choose the account the refund is paid from.'], 422);
            }
        }

        $product = Product::find($preOrder->product_id);
        DB::beginTransaction();
        try {
            if ($preOrder->serial_number) {
                $serial = ProductSerial::where('serial_number', $preOrder->serial_number)->lockForUpdate()->first();
                if ($serial && $serial->status !== 'sold') {
                    // the unit goes back on the shelf wherever it is now
                    $serial->status = 'available';
                    $serial->save();
                    $product->syncSerialStock($serial->warehouse_id);
                }
            }
            $preOrder->status = 'cancelled';
            $preOrder->cancel_reason = $request->cancel_reason;
            if ($refund > 0) {
                $preOrder->advance_refund_amount = $refund;
                $preOrder->advance_refund_account_id = $refundAccount->id;
                $preOrder->advance_refund_note = $request->cancel_reason;
                $preOrder->advance_refunded_at = now();
                $preOrder->advance_refunded_by = Auth::id();
            }
            $preOrder->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Cancel failed: ' . $e->getMessage()], 422);
        }

        $preOrder->load(['product', 'fromWarehouse', 'toWarehouse']);
        $kept = amount_format($advance - $refund);
        KgNotifier::toRoles(['Branch Manager', 'Seller', 'Manager', 'Admin'],
            "Cancelled: {$this->describe($preOrder)} ({$request->cancel_reason}). Advance refunded " . amount_format($refund) . ", kept {$kept}.",
            $this->poUrl($preOrder), 'pre_order', $preOrder->from_warehouse_id, Auth::id());

        return response()->json(['success' => true, 'message' => "Pre-Order #{$preOrder->order_no} cancelled."]);
    }

    // ------------------------------------------------------------------
    // delivery = the sale. Credit (report / commission) stays with the seller and branch that took the order.
    // ------------------------------------------------------------------
    public function convertToSale(Request $request, $id)
    {
        $preOrder = PreOrder::findOrFail($id);

        if (!$this->worksIn($preOrder->to_warehouse_id)) {
            return $this->deny('Delivery is done by the branch that took the order.');
        }
        if ($preOrder->status !== 'ready') {
            return response()->json([
                'error' => "Pre-order must be in 'ready' status to complete delivery (current status: {$preOrder->status})."
            ], 422);
        }
        if (!$preOrder->serial_number) {
            return response()->json(['error' => 'No serial number associated with this pre-order.'], 422);
        }

        $warehouseId = (int) $preOrder->to_warehouse_id;
        $product = Product::find($preOrder->product_id);
        $totalPrice = (float) $preOrder->price;
        $advance = (float) $preOrder->advance_amount;
        $due = round($totalPrice - $advance, 2);
        $seller = \App\Models\User::find($preOrder->user_id) ?? Auth::user();

        $dueAccount = null;
        if ($due > 0) {
            $method = $request->input('paying_method', 'Cash');
            $dueAccount = $request->pay_account_id ? Account::find($request->pay_account_id) : Account::defaultFor($method, $warehouseId);
            if (!$dueAccount || !$dueAccount->usableAt($warehouseId)) {
                return response()->json(['error' => 'The selected account cannot be used for this branch.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $serial = ProductSerial::where('serial_number', $preOrder->serial_number)->lockForUpdate()->first();
            if (!$serial) {
                DB::rollBack();
                return response()->json(['error' => 'Serial number not found.'], 422);
            }
            if ($serial->status !== 'ready' && $serial->status !== 'reserved') {
                DB::rollBack();
                return response()->json(['error' => "Serial status is '{$serial->status}', cannot convert to sale."], 422);
            }
            if ((int) $serial->warehouse_id !== $warehouseId) {
                DB::rollBack();
                return response()->json(['error' => 'The unit is not in the delivering branch yet.'], 422);
            }

            $cashRegister = CashRegister::where('user_id', Auth::id())->where('warehouse_id', $warehouseId)->where('status', true)->first();
            $saleRef = 'po-sale-' . date('Ymd') . '-' . date('his');

            // credit to the seller / branch that took the order, not to whoever hands it over
            $sale = Sale::create([
                'reference_no' => $saleRef,
                'user_id' => $seller->id,
                'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                'customer_id' => $preOrder->customer_id,
                'warehouse_id' => $warehouseId,
                'biller_id' => $seller->biller_id ?: 1,
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
                'payment_status' => 4,
                'paid_amount' => $totalPrice,
                'sale_note' => "Delivered from {$preOrder->kind_label} #{$preOrder->order_no}",
                'border_price_reason' => $preOrder->border_price_reason,
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

            $serial->markAsSold($sale->id);

            // The advance already sits in its account; it now simply belongs to this sale.
            Payment::where('pre_order_id', $preOrder->id)->whereNull('sale_id')->update(['sale_id' => $sale->id]);

            if ($due > 0) {
                Payment::create([
                    'payment_reference' => 'po-fin-' . date('Ymd') . '-' . $preOrder->id,
                    'user_id' => Auth::id(),
                    'sale_id' => $sale->id,
                    'pre_order_id' => $preOrder->id,
                    'cash_register_id' => $cashRegister ? $cashRegister->id : null,
                    'account_id' => $dueAccount->id,
                    'amount' => $due,
                    'change' => 0,
                    'paying_method' => $request->input('paying_method', 'Cash'),
                    'payment_note' => "{$preOrder->order_no} balance collected at delivery",
                    'payment_at' => date('Y-m-d H:i:s'),
                    'confirm_status' => $dueAccount->kind === 'gateway' ? 'pending' : 'confirmed',
                ]);
            }

            $preOrder->status = 'delivered';
            $preOrder->sale_id = $sale->id;
            $preOrder->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Conversion to sale failed: ' . $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "{$preOrder->kind_label} #{$preOrder->order_no} delivered and converted to Sale #{$saleRef}!",
            'sale_id' => $sale->id,
            'sale_ref' => $saleRef,
            'invoice_url' => url("sales/gen_invoice/{$sale->id}"),
        ]);
    }
}
