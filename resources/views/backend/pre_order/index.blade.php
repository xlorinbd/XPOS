@extends('backend.layout.main')

@section('content')
@php
    $kgAccountsJson = \App\Models\Account::where('is_active', true)->get(['id', 'name', 'type', 'warehouse_id'])->toJson();
    $kgRole = (int) Auth::user()->role_id;
@endphp
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="container-fluid">
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#interBranchLookupModal">
            <i class="dripicons-search"></i> Stock in Other Branches
        </button>&nbsp;
        <a href="{{ route('pre_orders.create') }}" class="btn btn-primary">
            <i class="dripicons-plus"></i> Add Pre-Order
        </a>
    </div>

    <div class="container-fluid mt-3">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Pre-Order & Inter-Branch Management</h3>
            </div>
            <div class="card-body">
                <div class="row align-items-center mb-3">
                    <div class="col-md-8">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('pre_orders.index', ['tab' => 'all', 'status' => $status]) }}">
                                    All Pre-Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === 'incoming' ? 'active' : '' }}" href="{{ route('pre_orders.index', ['tab' => 'incoming', 'status' => $status]) }}">
                                    Incoming Requests (Our Stock)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === 'outgoing' ? 'active' : '' }}" href="{{ route('pre_orders.index', ['tab' => 'outgoing', 'status' => $status]) }}">
                                    Outgoing Bookings (Our Customers)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-right">
                        <div class="d-inline-flex align-items-center">
                            <label class="mr-2 mb-0 font-weight-bold">Status:</label>
                            <select class="form-control selectpicker" onchange="location.href=this.value;" style="width:180px;">
                                <option value="{{ route('pre_orders.index', ['tab' => $tab]) }}" {{ !$status ? 'selected' : '' }}>All Statuses</option>
                                <option value="{{ route('pre_orders.index', ['tab' => $tab, 'status' => 'pending']) }}" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="{{ route('pre_orders.index', ['tab' => $tab, 'status' => 'confirmed']) }}" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="{{ route('pre_orders.index', ['tab' => $tab, 'status' => 'processing']) }}" {{ $status === 'processing' ? 'selected' : '' }}>In-Transit</option>
                                <option value="{{ route('pre_orders.index', ['tab' => $tab, 'status' => 'ready']) }}" {{ $status === 'ready' ? 'selected' : '' }}>Ready for Pickup</option>
                                <option value="{{ route('pre_orders.index', ['tab' => $tab, 'status' => 'delivered']) }}" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered (Sold)</option>
                                <option value="{{ route('pre_orders.index', ['tab' => $tab, 'status' => 'cancelled']) }}" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order No & Date</th>
                                <th>Customer</th>
                                <th>Device / Laptop</th>
                                <th>Source Branch</th>
                                <th>Destination</th>
                                <th>Price & Advance</th>
                                <th>Status</th>
                                <th class="not-exported text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($preOrders as $po)
                            @php
                                $due = round((float)$po->price - (float)$po->advance_amount, 2);
                                $statusBadge = match($po->status) {
                                    'pending' => 'badge-warning',
                                    'confirmed' => 'badge-info',
                                    'processing' => 'badge-primary',
                                    'ready' => 'badge-success',
                                    'delivered' => 'badge-secondary',
                                    'cancelled' => 'badge-danger',
                                    default => 'badge-secondary'
                                };
                                $userWhId = Auth::user()->warehouse_id;
                                $isSourceWh = (($userWhId == $po->from_warehouse_id) || (Auth::user()->role_id <= 2)) && $kgRole <= 3;
                                $isDestWh = ($userWhId == $po->to_warehouse_id) || (Auth::user()->role_id <= 2);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $po->order_no }}</strong>
                                    <span class="badge badge-light border">{{ $po->kind_label }}</span>
                                    <small class="text-muted d-block">{{ $po->created_at->format('d M Y, h:i A') }}</small>
                                </td>
                                <td>
                                    <strong>{{ $po->customer_name }}</strong>
                                    <small class="text-muted d-block">{{ $po->customer_phone }}</small>
                                </td>
                                <td>
                                    <strong>{{ $po->product ? $po->product->name : 'N/A' }}</strong>
                                    @if ($po->serial_number)
                                        <small class="text-info font-weight-bold d-block">
                                            S/N: <code>{{ $po->serial_number }}</code>
                                        </small>
                                    @else
                                        <small class="text-muted d-block italic">No serial assigned</small>
                                    @endif
                                </td>
                                <td>{{ $po->fromWarehouse ? $po->fromWarehouse->name : 'N/A' }}</td>
                                <td>{{ $po->toWarehouse ? $po->toWarehouse->name : 'N/A' }}</td>
                                <td>
                                    <div><strong>Total:</strong> {{ amount_format($po->price) }}</div>
                                    <small class="text-success d-block"><strong>Adv:</strong> {{ amount_format($po->advance_amount) }}</small>
                                    <small class="text-danger d-block"><strong>Due:</strong> {{ amount_format($due) }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $statusBadge }}">
                                        {{ $po->status === 'processing' ? 'In-Transit' : ucfirst($po->status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{trans("file.action")}}
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                            <li>
                                                <a href="{{ route('pre_orders.show', $po->id) }}" class="btn btn-link">
                                                    <i class="fa fa-eye"></i> View Details
                                                </a>
                                            </li>

                                            @if ($po->kind !== 'pre_booked' && $po->status === 'pending' && $isSourceWh)
                                                <li>
                                                    <button type="button" class="btn btn-link btn-update-status" data-id="{{ $po->id }}" data-status="confirmed" data-serial="{{ $po->serial_number }}">
                                                        <i class="dripicons-checkmark"></i> Confirm & Reserve Serial
                                                    </button>
                                                </li>
                                            @endif

                                            @if ($po->kind !== 'pre_booked' && $po->status === 'confirmed' && $isSourceWh)
                                                <li>
                                                    <button type="button" class="btn btn-link btn-update-status" data-id="{{ $po->id }}" data-status="processing">
                                                        <i class="fa fa-paper-plane"></i> Dispatch to Branch
                                                    </button>
                                                </li>
                                            @endif

                                            @if ($po->kind !== 'pre_booked' && $po->status === 'processing' && $isDestWh)
                                                <li>
                                                    <button type="button" class="btn btn-link btn-update-status" data-id="{{ $po->id }}" data-status="ready">
                                                        <i class="dripicons-inbox"></i> Receive & Mark Ready
                                                    </button>
                                                </li>
                                            @endif

                                            @if ($po->status === 'ready' && $isDestWh)
                                                <li>
                                                    <button type="button" class="btn btn-link btn-convert-sale" data-towh="{{ $po->to_warehouse_id }}" data-id="{{ $po->id }}" data-orderno="{{ $po->order_no }}" data-price="{{ $po->price }}" data-advance="{{ $po->advance_amount }}" data-due="{{ $due }}">
                                                        <i class="dripicons-cart"></i> Deliver & Finalize Sale
                                                    </button>
                                                </li>
                                            @endif

                                            @if ($po->status === 'delivered' && $po->sale_id)
                                                <li>
                                                    <a class="btn btn-link" href="{{ url('sales/gen_invoice/' . $po->sale_id) }}" target="_blank">
                                                        <i class="dripicons-print"></i> Print Invoice
                                                    </a>
                                                </li>
                                            @endif

                                            @if ($kgRole <= 2 && $po->kind !== 'pre_booked' && in_array($po->status, ['pending', 'confirmed']))
                                                <li>
                                                    <button type="button" class="btn btn-link btn-change-source" data-id="{{ $po->id }}" data-orderno="{{ $po->order_no }}">
                                                        <i class="dripicons-swap"></i> Change source branch
                                                    </button>
                                                </li>
                                            @endif

                                            @if ($kgRole <= 3 && !in_array($po->status, ['delivered', 'cancelled']))
                                                <li class="divider"></li>
                                                <li>
                                                    <button type="button" class="btn btn-link text-danger btn-cancel-po" data-id="{{ $po->id }}" data-orderno="{{ $po->order_no }}" data-advance="{{ $po->advance_amount }}" data-towh="{{ $po->to_warehouse_id }}">
                                                        <i class="dripicons-cross"></i> Cancel {{ $po->kind_label }}
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No pre-orders found for the selected criteria.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($preOrders->hasPages())
                <div class="d-flex justify-content-end mt-3">
                    {{ $preOrders->appends(['tab' => $tab, 'status' => $status])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Modal 1: Inter-Branch Live Stock Lookup -->
<div id="interBranchLookupModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Live Inter-Branch Stock Lookup</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><strong>Select Product to Check Availability *</strong></label>
                    <select id="modal_product_select" class="form-control selectpicker" data-live-search="true" title="Search gadget/laptop...">
                        @php
                            $availableProds = \App\Models\Product::where('is_active', true)->where('is_imei', true)->get();
                        @endphp
                        @foreach ($availableProds as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div id="branchStockResult" class="d-none mt-3">
                    <div class="card card-body bg-light mb-3">
                        <h5 id="lookupProdName" class="font-weight-bold mb-1"></h5>
                        <small id="lookupProdSpecs" class="text-muted font-weight-bold"></small>
                    </div>

                    <h6><strong>Branch Inventory Breakdown:</strong></h6>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Branch / Warehouse</th>
                                    <th>Available Stock</th>
                                    <th>Available Serials</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="branchRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Book Pre-Order Modal -->
<div id="bookPreOrderModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel2" class="modal-title">Book Inter-Branch Pre-Order</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="bookPreOrderForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="book_product_id" name="product_id">
                <input type="hidden" id="book_from_warehouse_id" name="from_warehouse_id">
                <div class="modal-body">
                    <div class="card card-body bg-light mb-3 small">
                        <strong>Product:</strong> <span id="book_prod_name"></span><br>
                        <strong>Source Branch:</strong> <span id="book_from_name" class="font-weight-bold"></span><br>
                        <strong>Serial:</strong> <span id="book_serial_display" class="font-weight-bold">Any / Assigned on Dispatch</span>
                        <input type="hidden" id="book_serial_number" name="serial_number">
                    </div>

                    <div class="form-group">
                        <label><strong>Customer Name *</strong></label>
                        <input type="text" name="customer_name" class="form-control" required placeholder="Enter customer name...">
                    </div>

                    <div class="form-group">
                        <label><strong>Customer Phone Number *</strong></label>
                        <input type="text" name="customer_phone" class="form-control" required placeholder="017xxxxxxxx">
                    </div>

                    <div class="form-group">
                        <label><strong>Destination Branch (Pickup Location) *</strong></label>
                        <select name="to_warehouse_id" id="book_to_warehouse" class="form-control selectpicker" required>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" {{ Auth::user()->warehouse_id == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><strong>Agreed Price *</strong></label>
                            <input type="number" id="book_price" name="price" class="form-control" required min="0" step="any">
                        </div>
                        <div class="col-md-6 form-group">
                            <label><strong>Advance Deposit * (required)</strong></label>
                            <input type="number" id="book_advance" name="advance_amount" class="form-control" min="1" step="any" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><strong>Advance Payment Method *</strong></label>
                            <select name="paying_method" id="book_method" class="form-control">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Banking">Mobile Banking</option>
                                <option value="Card">Card</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label><strong>Goes into account</strong></label>
                            <select name="advance_account_id" id="book_account" class="form-control"></select>
                        </div>
                    </div>
                    <small class="text-muted d-block mb-2" id="book_kind_hint"></small>

                    <div class="form-group">
                        <label><strong>Booking Notes / Remarks</strong></label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Special customer instructions..."></textarea>
                    </div>

                    <div id="bookingAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{trans("file.Cancel")}}</button>
                    <button type="submit" id="btnConfirmBook" class="btn btn-primary">
                        Confirm Pre-Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Convert to Sale (Final Delivery) -->
<div id="deliverySaleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel3" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel3" class="modal-title">Deliver & Convert to Sale</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="deliverySaleForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="convert_pre_order_id">
                <div class="modal-body">
                    <div class="card card-body bg-light mb-3">
                        <h5 class="mb-1" id="convert_order_no"></h5>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Total Price:</span>
                            <strong class="text-dark" id="convert_total_price"></strong>
                        </div>
                        <div class="d-flex justify-content-between small text-success mb-1">
                            <span>Advance Already Paid:</span>
                            <strong id="convert_advance_paid"></strong>
                        </div>
                        <div class="d-flex justify-content-between font-weight-bold border-top pt-1 text-danger">
                            <span>Remaining Balance Due:</span>
                            <span id="convert_balance_due"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Payment Method for Remaining Due *</strong></label>
                        <select id="convert_paying_method" name="paying_method" class="form-control selectpicker">
                            <option value="Cash">Cash</option>
                            <option value="Card">Credit / Debit Card</option>
                            <option value="Bkash">Bkash / Nagad</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><strong>Goes into account</strong></label>
                        <select id="convert_account" name="pay_account_id" class="form-control"></select>
                    </div>

                    <div id="convertAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{trans("file.Cancel")}}</button>
                    <button type="submit" id="btnConfirmDelivery" class="btn btn-primary">
                        Deliver & Print Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Cancel with advance refund decision -->
<div id="cancelPreOrderModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel <span id="cancel_order_no"></span></h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="cancelPreOrderForm" onsubmit="return false;">
                <input type="hidden" id="cancel_pre_order_id">
                <div class="modal-body">
                    <p class="small text-muted mb-2">Advance received: <strong id="cancel_advance"></strong>. How much goes back to the customer is your decision for each case; whatever you do not refund stays with the shop.</p>
                    <div class="form-group">
                        <label><strong>Reason *</strong></label>
                        <input type="text" id="cancel_reason" class="form-control" maxlength="255" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><strong>Refund amount</strong></label>
                            <input type="number" id="cancel_refund" class="form-control" min="0" step="any" value="0">
                        </div>
                        <div class="col-md-6 form-group">
                            <label><strong>Refund paid from account</strong></label>
                            <select id="cancel_refund_account" class="form-control"></select>
                        </div>
                    </div>
                    <div id="cancelAlert" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Back</button>
                    <button type="submit" id="btnConfirmCancel" class="btn btn-danger">Cancel this order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 5: Change source branch (Manager / Admin) -->
<div id="changeSourceModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change source branch <span id="src_order_no"></span></h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="changeSourceForm" onsubmit="return false;">
                <input type="hidden" id="src_pre_order_id">
                <div class="modal-body">
                    <p class="small text-muted">Pick the branch the unit should come from instead. The current reservation is released and a unit at the new branch is reserved.</p>
                    <div class="form-group">
                        <label><strong>New source branch *</strong></label>
                        <select id="src_new_from" class="form-control">
                            @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                        </select>
                    </div>
                    <div id="srcAlert" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-dark">Change source</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    var kgAccounts = {!! $kgAccountsJson !!};

    $(document).ready(function() {
        function refreshBookAccounts() {
            kgFillAccounts($('#book_account'), kgAccounts, $('#book_to_warehouse').val(), $('#book_method').val());
            var same = String($('#book_from_warehouse_id').val()) === String($('#book_to_warehouse').val());
            $('#book_kind_hint').text(same ? 'Same branch: this is a Pre-Booking (the unit is kept aside here).' : 'Different branch: this is a Pre-Order (the unit is sent from the source branch).');
        }
        $('#book_to_warehouse, #book_method').on('change changed.bs.select', refreshBookAccounts);
        $('#bookPreOrderModal').on('shown.bs.modal', refreshBookAccounts);

        // 1. Inter-Branch Stock Lookup
        $('#modal_product_select').on('change', function() {
            var pid = $(this).val();
            if (!pid) return;

            $.ajax({
                url: '{{ url("pre_orders/stock") }}/' + pid,
                type: 'GET',
                success: function(d) {
                    if (d.success) {
                        $('#lookupProdName').text(d.product_name + ' (' + d.product_code + ')');
                        $('#lookupProdSpecs').html(d.specs_line + ' [' + d.product_condition + ']');
                        $('#branchRows').empty();

                        d.branches.forEach(function(b) {
                            var serialsHtml = '';
                            if (b.serials && b.serials.length > 0) {
                                b.serials.forEach(function(s) {
                                    serialsHtml += '<button type="button" class="btn btn-outline-secondary btn-sm m-1 btn-book-specific-serial" data-pid="' + d.product_id + '" data-pname="' + d.product_name + '" data-price="' + d.price + '" data-wid="' + b.warehouse_id + '" data-wname="' + b.warehouse_name + '" data-serial="' + s.serial_number + '"><i class="fa fa-barcode"></i> ' + s.serial_number + '</button>';
                                });
                            } else {
                                serialsHtml = '<span class="text-muted italic small">No serials</span>';
                            }

                            var bookBtn = '<button type="button" class="btn btn-sm btn-info btn-book-generic" data-pid="' + d.product_id + '" data-pname="' + d.product_name + '" data-price="' + d.price + '" data-wid="' + b.warehouse_id + '" data-wname="' + b.warehouse_name + '"><i class="dripicons-box"></i> Book from here</button>';
                            if (b.stock_qty <= 0) {
                                bookBtn = '<button type="button" class="btn btn-sm btn-secondary" disabled>Out of Stock</button>';
                            }

                            var row = '<tr>' +
                                '<td><strong>' + b.warehouse_name + '</strong></td>' +
                                '<td><span class="badge badge-info">' + b.stock_qty + ' units</span></td>' +
                                '<td>' + serialsHtml + '</td>' +
                                '<td class="text-center">' + bookBtn + '</td>' +
                            '</tr>';
                            $('#branchRows').append(row);
                        });

                        $('#branchStockResult').removeClass('d-none');
                    }
                }
            });
        });

        // 2. Open Pre-Order Booking Modal
        $(document).on('click', '.btn-book-generic, .btn-book-specific-serial', function() {
            var pid = $(this).data('pid');
            var pname = $(this).data('pname');
            var price = $(this).data('price');
            var wid = $(this).data('wid');
            var wname = $(this).data('wname');
            var serial = $(this).data('serial') || '';

            $('#book_product_id').val(pid);
            $('#book_prod_name').text(pname);
            $('#book_price').val(price);
            $('#book_from_warehouse_id').val(wid);
            $('#book_from_name').text(wname);
            $('#book_serial_number').val(serial);

            if (serial) {
                $('#book_serial_display').html('<code>' + serial + '</code> (Reserved)');
            } else {
                $('#book_serial_display').text('Any available unit (Assigned on dispatch)');
            }

            $('#interBranchLookupModal').modal('hide');
            $('#bookPreOrderModal').modal('show');
        });

        // Submit Pre-Order Booking
        $('#bookPreOrderForm').on('submit', function(e) {
            e.preventDefault();
            $('#btnConfirmBook').prop('disabled', true).text('Booking...');
            $('#bookingAlert').addClass('d-none');

            $.ajax({
                url: '{{ route("pre_orders.store") }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function(resp) {
                    $('#btnConfirmBook').prop('disabled', false).text('Confirm Pre-Order');
                    if (resp && resp.success) {
                        $('#bookingAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    $('#btnConfirmBook').prop('disabled', false).text('Confirm Pre-Order');
                    if (xhr.responseJSON && xhr.responseJSON.needs_border_reason) {
                        kgBorderReason(xhr.responseJSON.lines, function (reason) {
                            $('#bookPreOrderForm input[name="border_price_reason"]').remove();
                            $('#bookPreOrderForm').append($('<input type="hidden" name="border_price_reason">').val(reason)).trigger('submit');
                        });
                        return;
                    }
                    var msg = 'Failed to book pre-order.';
                    if (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) {
                        msg = xhr.responseJSON.error || xhr.responseJSON.message;
                    }
                    $('#bookingAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });

        // 3. Status Transition Handshake
        $(document).on('click', '.btn-update-status', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var status = $(this).data('status');
            var serial = $(this).data('serial') || '';

            var confirmMsg = 'Are you sure you want to update this pre-order to ' + status.toUpperCase() + '?';
            if (status === 'ready') {
                confirmMsg = 'Confirm receipt: Has this laptop physically arrived at your branch? Clicking OK will transfer serial warehouse ownership to your branch and mark it ready.';
            }

            if (!confirm(confirmMsg)) return;

            $.ajax({
                url: '{{ url("pre_orders") }}/' + id + '/status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status,
                    serial_number: serial
                },
                success: function(resp) {
                    if (resp && resp.success) {
                        alert(resp.message);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    var msg = 'Failed to update status.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    alert(msg);
                }
            });
        });

        // 4. Open Delivery Sale Conversion Modal
        $(document).on('click', '.btn-convert-sale', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var orderno = $(this).data('orderno');
            var price = parseFloat($(this).data('price'));
            var advance = parseFloat($(this).data('advance'));
            var due = parseFloat($(this).data('due'));

            $('#convert_pre_order_id').val(id);
            $('#convert_order_no').text(orderno);
            $('#convert_total_price').text(price.toFixed(2));
            $('#convert_advance_paid').text(advance.toFixed(2));
            $('#convert_balance_due').text(due.toFixed(2));

            kgFillAccounts($('#convert_account'), kgAccounts, $(this).data('towh'), $('#convert_paying_method').val());
            $('#convert_paying_method').data('towh', $(this).data('towh'));
            $('#deliverySaleModal').modal('show');
        });

        $('#convert_paying_method').on('change changed.bs.select', function () {
            kgFillAccounts($('#convert_account'), kgAccounts, $(this).data('towh'), $(this).val());
        });

        // Submit Delivery Conversion
        $('#deliverySaleForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#convert_pre_order_id').val();
            var method = $('#convert_paying_method').val();

            $('#btnConfirmDelivery').prop('disabled', true).text('Delivering...');
            $('#convertAlert').addClass('d-none');

            $.ajax({
                url: '{{ url("pre_orders") }}/' + id + '/convert',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    paying_method: method,
                    pay_account_id: $('#convert_account').val()
                },
                success: function(resp) {
                    $('#btnConfirmDelivery').prop('disabled', false).text('Deliver & Print Invoice');
                    if (resp && resp.success) {
                        $('#convertAlert').removeClass('d-none alert-danger').addClass('alert alert-success')
                            .html('<strong>' + resp.message + '</strong><br><a href="' + resp.invoice_url + '" target="_blank" class="btn btn-sm btn-info mt-2"><i class="dripicons-print"></i> Print Invoice</a>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function(xhr) {
                    $('#btnConfirmDelivery').prop('disabled', false).text('Deliver & Print Invoice');
                    var msg = 'Conversion failed.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#convertAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
        // 5. Cancel with advance refund decision
        $(document).on('click', '.btn-cancel-po', function () {
            var advance = parseFloat($(this).data('advance')) || 0;
            $('#cancel_pre_order_id').val($(this).data('id'));
            $('#cancel_order_no').text($(this).data('orderno'));
            $('#cancel_advance').text(kgMoney(advance));
            $('#cancel_refund').val(0).attr('max', advance);
            $('#cancel_reason').val('');
            $('#cancelAlert').addClass('d-none');
            kgFillAccounts($('#cancel_refund_account'), kgAccounts, $(this).data('towh'), 'cash');
            $('#cancelPreOrderModal').modal('show');
        });
        $('#cancelPreOrderForm').on('submit', function (e) {
            e.preventDefault();
            $('#btnConfirmCancel').prop('disabled', true);
            $.post('{{ url("pre_orders") }}/' + $('#cancel_pre_order_id').val() + '/cancel', {
                _token: '{{ csrf_token() }}',
                cancel_reason: $('#cancel_reason').val(),
                refund_amount: $('#cancel_refund').val(),
                refund_account_id: $('#cancel_refund_account').val()
            }).done(function (resp) { location.reload(); })
              .fail(function (xhr) {
                  $('#btnConfirmCancel').prop('disabled', false);
                  var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Cancel failed.';
                  $('#cancelAlert').removeClass('d-none').addClass('alert alert-danger').text(msg);
              });
        });

        // 6. Change source branch
        $(document).on('click', '.btn-change-source', function () {
            $('#src_pre_order_id').val($(this).data('id'));
            $('#src_order_no').text($(this).data('orderno'));
            $('#srcAlert').addClass('d-none');
            $('#changeSourceModal').modal('show');
        });
        $('#changeSourceForm').on('submit', function (e) {
            e.preventDefault();
            $.post('{{ url("pre_orders") }}/' + $('#src_pre_order_id').val() + '/change-source', {
                _token: '{{ csrf_token() }}',
                from_warehouse_id: $('#src_new_from').val()
            }).done(function () { location.reload(); })
              .fail(function (xhr) {
                  var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Could not change the source.';
                  $('#srcAlert').removeClass('d-none').addClass('alert alert-danger').text(msg);
              });
        });
    });
</script>
@endpush
