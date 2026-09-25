<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Lookup;
use App\Models\ProductPurchase;
use App\Models\Purchase;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ShipmentController extends Controller
{
    public function __construct(private ShipmentService $service)
    {
    }

    private function allowed(string $permission): bool
    {
        $role = Role::find(Auth::user()->role_id);
        return $role && $role->hasPermissionTo($permission);
    }

    private function deny()
    {
        return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function index(Request $request)
    {
        if (!$this->allowed('purchases-index')) {
            return $this->deny();
        }

        $status = $request->input('status');
        $shipments = Shipment::with('warehouse')->withCount('items')
            ->when($status && isset(Shipment::STATUSES[$status]), fn($q) => $q->where('status', $status))
            ->orderByDesc('id')->get();

        $counts = Shipment::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->all();

        return view('backend.shipment.index', compact('shipments', 'status', 'counts'));
    }

    public function create()
    {
        if (!$this->allowed('purchases-add')) {
            return $this->deny();
        }

        $lines = $this->openForeignLines();
        $warehouses = Warehouse::where('is_active', true)->orderBy('type')->orderBy('name')->get();
        $defaultWarehouse = $warehouses->firstWhere('type', 'warehouse')->id ?? $warehouses->first()->id ?? null;
        $cargoCompanies = Lookup::ofType('cargo_company')->active()->orderBy('name')->get();
        $handCarry = Lookup::ofType('hand_carry_person')->active()->orderBy('name')->get();
        $accounts = Account::where('is_active', true)->get();

        return view('backend.shipment.create', compact('lines', 'warehouses', 'defaultWarehouse', 'cargoCompanies', 'handCarry', 'accounts'));
    }

    public function store(Request $request)
    {
        if (!$this->allowed('purchases-add')) {
            return $this->deny();
        }

        $data = $request->validate([
            'shipment_type' => 'required|in:cargo,hand_carry,other',
            'other_method' => 'nullable|string|max:191',
            'carrier_name' => 'nullable|string|max:191',
            'carrier_contact' => 'nullable|string|max:191',
            'tracking_no' => 'nullable|string|max:191',
            'responsible_person' => 'nullable|string|max:191',
            'shipment_date' => 'nullable|date',
            'expected_arrival' => 'nullable|date',
            'destination_warehouse_id' => 'required|integer|exists:warehouses,id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'customs_cost' => 'nullable|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'cost_account_id' => 'nullable|integer',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
        ]);

        try {
            $shipment = $this->service->create($data, $request->input('items', []), Auth::id());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('shipments.show', $shipment->id)->with('message', 'Shipment ' . $shipment->reference_no . ' created. The products stay In-Transit until they are received in the Bangladesh warehouse.');
    }

    public function show($id)
    {
        if (!$this->allowed('purchases-index')) {
            return $this->deny();
        }

        $shipment = Shipment::with(['items.product', 'items.purchase', 'items.productPurchase', 'warehouse', 'expense', 'creator'])->findOrFail($id);
        $accounts = Account::where('is_active', true)->get();
        $trackDefault = [];
        foreach ($shipment->items as $item) {
            $p = $item->product;
            $untracked = $p ? max(0, (int) round((float) $p->qty) - \App\Models\ProductSerial::where('product_id', $p->id)->where('status', 'available')->count()) : 0;
            $trackDefault[$item->id] = $p && $untracked === 0 && ($p->is_imei || $p->processor || $p->model);
        }
        $pieces = (int) $shipment->items->sum('qty_received');

        return view('backend.shipment.show', compact('shipment', 'accounts', 'trackDefault', 'pieces'));
    }

    public function receive(Request $request, $id)
    {
        if (!$this->allowed('purchases-add')) {
            return $this->deny();
        }
        $shipment = Shipment::with('items')->findOrFail($id);

        try {
            $this->service->markReceived($shipment, $request->input('received', []), $request->input('actual_arrival'), Auth::id());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->route('shipments.show', $id)->with('message', 'Marked as received in the Bangladesh warehouse. Enter serial numbers and costs for the final entry.');
    }

    public function finalize(Request $request, $id)
    {
        if (!$this->allowed('purchases-add')) {
            return $this->deny();
        }
        $shipment = Shipment::with('items')->findOrFail($id);

        $costs = $request->validate([
            'shipping_cost' => 'nullable|numeric|min:0',
            'customs_cost' => 'nullable|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'cost_account_id' => 'nullable|integer',
        ]);

        try {
            $this->service->finalize($shipment, $costs, $request->input('lines', []), Auth::id());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('shipments.show', $id)->with('message', 'Final entry done. The products are now in stock and can be sold.');
    }

    public function cancel($id)
    {
        if (!$this->allowed('purchases-add')) {
            return $this->deny();
        }
        $shipment = Shipment::findOrFail($id);
        try {
            $this->service->cancel($shipment);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
        return redirect()->route('shipments.index')->with('message', 'Shipment cancelled.');
    }

    /**
     * Products on foreign purchases that have not reached stock yet (not part of sellable stock).
     */
    public function inTransit()
    {
        if (!$this->allowed('purchases-index')) {
            return $this->deny();
        }

        $rows = ProductPurchase::query()
            ->join('purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
            ->join('products', 'products.id', '=', 'product_purchases.product_id')
            ->where('purchases.purchase_type', 'foreign')
            ->whereNull('purchases.deleted_at')
            ->whereRaw('product_purchases.qty > product_purchases.recieved')
            ->select('product_purchases.id as line_id', 'purchases.id as purchase_id', 'purchases.reference_no', 'products.id as product_id',
                'products.name', 'products.model', 'product_purchases.qty', 'product_purchases.recieved')
            ->orderBy('purchases.id')
            ->get();

        $onShipment = ShipmentItem::query()
            ->join('shipments', 'shipments.id', '=', 'shipment_items.shipment_id')
            ->whereIn('shipments.status', ['in_transit', 'received'])
            ->groupBy('shipment_items.product_purchase_id')
            ->selectRaw('shipment_items.product_purchase_id as id, SUM(shipment_items.qty_shipped) as qty')
            ->pluck('qty', 'id');

        $byProduct = [];
        foreach ($rows as $r) {
            $open = (float) $r->qty - (float) $r->recieved;
            $shipped = min($open, (float) ($onShipment[$r->line_id] ?? 0));
            $key = $r->product_id;
            $byProduct[$key] = $byProduct[$key] ?? ['name' => $r->name, 'model' => $r->model, 'open' => 0, 'shipped' => 0, 'purchases' => []];
            $byProduct[$key]['open'] += $open;
            $byProduct[$key]['shipped'] += $shipped;
            $byProduct[$key]['purchases'][$r->purchase_id] = $r->reference_no;
        }
        uasort($byProduct, fn($a, $b) => strcmp($a['name'], $b['name']));

        return view('backend.shipment.in_transit', compact('byProduct'));
    }

    private function openForeignLines()
    {
        $lines = ProductPurchase::query()
            ->join('purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
            ->join('products', 'products.id', '=', 'product_purchases.product_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchases.purchase_type', 'foreign')
            ->whereNull('purchases.deleted_at')
            ->select('product_purchases.*', 'purchases.reference_no', 'purchases.created_at as purchase_date',
                'products.name as product_name', 'products.model as product_model', 'suppliers.name as supplier_name')
            ->orderBy('purchases.id')
            ->get();

        return $lines->map(function ($l) {
            $l->remaining = $this->service->remainingToShip($l);
            return $l;
        })->filter(fn($l) => $l->remaining > 0)->values();
    }
}
