@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-truck mr-2"></i> Inter-Branch Pre-Order & Request Management</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Cross-branch inventory lookup, reservation, transfer tracking, and final delivery conversion to sales.</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <button type="button" class="btn btn-warning font-weight-bold shadow-sm" data-toggle="modal" data-target="#interBranchLookupModal">
                                <i class="fa fa-search mr-1"></i> Stock in Other Branches
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs & Filters -->
                <div class="card-body bg-light border-bottom py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <!-- Navigation Tabs -->
                        <ul class="nav nav-pills mb-2 mb-md-0">
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('pre_orders.index', ['tab' => 'all', 'status' => $status]) }}">
                                    All Pre-Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold {{ $tab === 'incoming' ? 'active' : '' }}" href="{{ route('pre_orders.index', ['tab' => 'incoming', 'status' => $status]) }}">
                                    <i class="fa fa-arrow-down text-warning mr-1"></i> Incoming Requests (Our Stock)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold {{ $tab === 'outgoing' ? 'active' : '' }}" href="{{ route('pre_orders.index', ['tab' => 'outgoing', 'status' => $status]) }}">
                                    <i class="fa fa-arrow-up text-info mr-1"></i> Outgoing Bookings (Our Customers)
                                </a>
                            </li>
                        </ul>

                        <!-- Status Filter Dropdown -->
                        <div class="d-flex align-items-center">
                            <span class="mr-2 font-weight-bold small text-muted">Status:</span>
                            <select class="form-control form-control-sm font-weight-bold" onchange="location.href=this.value;" style="width:170px;">
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

                <!-- Pre-Orders Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light" style="font-size:12px; text-transform:uppercase;">
                                <tr>
                                    <th>Order No & Date</th>
                                    <th>Customer</th>
                                    <th>Device / Laptop</th>
                                    <th>Source Branch</th>
                                    <th>Destination</th>
                                    <th>Price & Advance</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
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
                                        'delivered' => 'badge-dark',
                                        'cancelled' => 'badge-danger',
                                        default => 'badge-secondary'
                                    };
                                    $userWhId = Auth::user()->warehouse_id;
                                    $isSourceWh = ($userWhId == $po->from_warehouse_id) || (Auth::user()->role_id <= 2);
                                    $isDestWh = ($userWhId == $po->to_warehouse_id) || (Auth::user()->role_id <= 2);
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="text-primary">{{ $po->order_no }}</strong>
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
                                                <i class="fa fa-barcode"></i> S/N: <code>{{ $po->serial_number }}</code>
                                            </small>
                                        @else
                                            <small class="text-muted d-block italic">No serial assigned yet</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">{{ $po->fromWarehouse ? $po->fromWarehouse->name : 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-primary">{{ $po->toWarehouse ? $po->toWarehouse->name : 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <div><strong>Total:</strong> ৳{{ number_format($po->price, 2) }}</div>
                                        <small class="text-success d-block"><strong>Adv:</strong> ৳{{ number_format($po->advance_amount, 2) }}</small>
                                        <small class="text-danger d-block"><strong>Due:</strong> ৳{{ number_format($due, 2) }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusBadge }} px-2 py-1 font-weight-bold text-uppercase" style="font-size:11px;">
                                            {{ $po->status === 'processing' ? 'In-Transit' : $po->status }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                                Action
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                @if ($po->status === 'pending' && $isSourceWh)
                                                    <a class="dropdown-item text-primary font-weight-bold btn-update-status" href="#" data-id="{{ $po->id }}" data-status="confirmed" data-serial="{{ $po->serial_number }}">
                                                        <i class="fa fa-check mr-1"></i> Confirm & Reserve Serial
                                                    </a>
                                                @endif

                                                @if ($po->status === 'confirmed' && $isSourceWh)
                                                    <a class="dropdown-item text-info font-weight-bold btn-update-status" href="#" data-id="{{ $po->id }}" data-status="processing">
                                                        <i class="fa fa-paper-plane mr-1"></i> Dispatch / Send to Branch
                                                    </a>
                                                @endif

                                                @if ($po->status === 'processing' && $isDestWh)
                                                    <a class="dropdown-item text-success font-weight-bold btn-update-status" href="#" data-id="{{ $po->id }}" data-status="ready">
                                                        <i class="fa fa-inbox mr-1"></i> Receive & Mark Ready
                                                    </a>
                                                @endif

                                                @if ($po->status === 'ready' && $isDestWh)
                                                    <a class="dropdown-item text-success font-weight-bold btn-convert-sale" href="#" data-id="{{ $po->id }}" data-orderno="{{ $po->order_no }}" data-price="{{ $po->price }}" data-advance="{{ $po->advance_amount }}" data-due="{{ $due }}">
                                                        <i class="fa fa-shopping-cart mr-1"></i> Deliver & Finalize Sale
                                                    </a>
                                                @endif

                                                @if ($po->status === 'delivered' && $po->sale_id)
                                                    <a class="dropdown-item text-dark font-weight-bold" href="{{ url('sales/gen_invoice/' . $po->sale_id) }}" target="_blank">
                                                        <i class="fa fa-print mr-1"></i> Print Invoice
                                                    </a>
                                                @endif

                                                @if (!in_array($po->status, ['delivered', 'cancelled']))
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger btn-update-status" href="#" data-id="{{ $po->id }}" data-status="cancelled">
                                                        <i class="fa fa-times mr-1"></i> Cancel Pre-Order
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa fa-inbox fa-3x mb-2 d-block text-muted"></i>
                                        No pre-orders found for the selected criteria.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if ($preOrders->hasPages())
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                    {{ $preOrders->appends(['tab' => $tab, 'status' => $status])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Inter-Branch Live Stock Lookup -->
<div class="modal fade" id="interBranchLookupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-search mr-2"></i> Live Inter-Branch Stock Lookup</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-4">
                    <label class="font-weight-bold">Select Product to Check Availability *</label>
                    <select id="modal_product_select" class="form-control selectpicker" data-live-search="true" title="Search gadget/laptop...">
                        @php
                            $availableProds = \App\Models\Product::where('is_active', true)->where('is_imei', true)->get();
                        @endphp
                        @foreach ($availableProds as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div id="branchStockResult" class="d-none">
                    <div class="p-3 bg-light rounded border mb-3">
                        <h5 id="lookupProdName" class="font-weight-bold text-primary mb-1"></h5>
                        <small id="lookupProdSpecs" class="text-muted d-block font-weight-bold"></small>
                    </div>

                    <h6 class="font-weight-bold text-dark mb-2">Branch Inventory Breakdown:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light small font-weight-bold">
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
<div class="modal fade" id="bookPreOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header bg-dark text-white" style="border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-truck mr-2"></i> Book Inter-Branch Pre-Order</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="bookPreOrderForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="book_product_id" name="product_id">
                <input type="hidden" id="book_from_warehouse_id" name="from_warehouse_id">
                <div class="modal-body p-4">
                    <div class="p-2 bg-light rounded border mb-3 small">
                        <strong>Product:</strong> <span id="book_prod_name"></span><br>
                        <strong>Source Branch:</strong> <span id="book_from_name" class="text-warning font-weight-bold"></span><br>
                        <strong>Serial:</strong> <span id="book_serial_display" class="text-info font-weight-bold">Any / Assigned on Dispatch</span>
                        <input type="hidden" id="book_serial_number" name="serial_number">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Customer Name *</label>
                        <input type="text" name="customer_name" class="form-control" required placeholder="Enter customer name...">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Customer Phone Number *</label>
                        <input type="text" name="customer_phone" class="form-control" required placeholder="017xxxxxxxx">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Destination Branch (Pickup Location) *</label>
                        <select name="to_warehouse_id" class="form-control" required>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" {{ Auth::user()->warehouse_id == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">Agreed Price (৳) *</label>
                            <input type="number" id="book_price" name="price" class="form-control font-weight-bold" required min="0" step="any">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">Advance Deposit (৳)</label>
                            <input type="number" name="advance_amount" class="form-control font-weight-bold text-success" value="0" min="0" step="any">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Advance Payment Method</label>
                        <select name="paying_method" class="form-control form-control-sm">
                            <option value="Cash">Cash</option>
                            <option value="Bkash">Bkash / MFS</option>
                            <option value="Card">Card</option>
                            <option value="Bank">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Booking Notes / Remarks</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Special customer instructions..."></textarea>
                    </div>

                    <div id="bookingAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnConfirmBook" class="btn btn-primary font-weight-bold px-4">
                        <i class="fa fa-check mr-1"></i> Confirm Pre-Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Convert to Sale (Final Delivery) -->
<div class="modal fade" id="deliverySaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header bg-success text-white" style="border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-check-circle mr-2"></i> Deliver & Convert to Sale</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="deliverySaleForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="convert_pre_order_id">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded border mb-3">
                        <h5 class="mb-1 font-weight-bold" id="convert_order_no"></h5>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Total Price:</span>
                            <strong class="text-dark" id="convert_total_price"></strong>
                        </div>
                        <div class="d-flex justify-content-between small text-success mb-1">
                            <span>Advance Already Paid:</span>
                            <strong id="convert_advance_paid"></strong>
                        </div>
                        <div class="d-flex justify-content-between font-weight-bold border-top pt-1 text-danger" style="font-size:15px;">
                            <span>Remaining Balance Due:</span>
                            <span id="convert_balance_due"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Payment Method for Remaining Due *</label>
                        <select id="convert_paying_method" name="paying_method" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="Card">Credit / Debit Card</option>
                            <option value="Bkash">Bkash / Nagad</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div id="convertAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Close</button>
                    <button type="submit" id="btnConfirmDelivery" class="btn btn-success font-weight-bold px-4">
                        <i class="fa fa-shopping-cart mr-1"></i> Deliver & Print Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
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
                        $('#lookupProdSpecs').html('<i class="fa fa-laptop text-info mr-1"></i> ' + d.specs_line + ' [' + d.product_condition + ']');
                        $('#branchRows').empty();

                        d.branches.forEach(function(b) {
                            var serialsHtml = '';
                            if (b.serials && b.serials.length > 0) {
                                b.serials.forEach(function(s) {
                                    serialsHtml += '<button type="button" class="btn btn-outline-dark btn-xs m-1 btn-book-specific-serial font-weight-bold" data-pid="' + d.product_id + '" data-pname="' + d.product_name + '" data-price="' + d.price + '" data-wid="' + b.warehouse_id + '" data-wname="' + b.warehouse_name + '" data-serial="' + s.serial_number + '"><i class="fa fa-barcode"></i> ' + s.serial_number + '</button>';
                                });
                            } else {
                                serialsHtml = '<span class="text-muted italic small">No serials</span>';
                            }

                            var bookBtn = '<button type="button" class="btn btn-sm btn-primary font-weight-bold btn-book-generic" data-pid="' + d.product_id + '" data-pname="' + d.product_name + '" data-price="' + d.price + '" data-wid="' + b.warehouse_id + '" data-wname="' + b.warehouse_name + '"><i class="fa fa-truck mr-1"></i> Book from here</button>';
                            if (b.stock_qty <= 0) {
                                bookBtn = '<button type="button" class="btn btn-sm btn-secondary font-weight-bold" disabled>Out of Stock</button>';
                            }

                            var row = '<tr>' +
                                '<td><strong>' + b.warehouse_name + '</strong></td>' +
                                '<td><span class="badge badge-info px-2 py-1">' + b.stock_qty + ' units</span></td>' +
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
                $('#book_serial_display').html('<i class="fa fa-barcode"></i> <code>' + serial + '</code> (Reserved)');
            } else {
                $('#book_serial_display').text('Any available unit (Assigned on dispatch)');
            }

            $('#interBranchLookupModal').modal('hide');
            $('#bookPreOrderModal').modal('show');
        });

        // Submit Pre-Order Booking
        $('#bookPreOrderForm').on('submit', function(e) {
            e.preventDefault();
            $('#btnConfirmBook').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Booking...');
            $('#bookingAlert').addClass('d-none');

            $.ajax({
                url: '{{ route("pre_orders.store") }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function(resp) {
                    $('#btnConfirmBook').prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Confirm Pre-Order');
                    if (resp && resp.success) {
                        $('#bookingAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    }
                },
                error: function(xhr) {
                    $('#btnConfirmBook').prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Confirm Pre-Order');
                    var msg = 'Failed to book pre-order.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        msg = xhr.responseJSON.error;
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
            $('#convert_total_price').text('৳ ' + price.toFixed(2));
            $('#convert_advance_paid').text('৳ ' + advance.toFixed(2));
            $('#convert_balance_due').text('৳ ' + due.toFixed(2));

            $('#deliverySaleModal').modal('show');
        });

        // Submit Delivery Conversion
        $('#deliverySaleForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#convert_pre_order_id').val();
            var method = $('#convert_paying_method').val();

            $('#btnConfirmDelivery').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Delivering...');
            $('#convertAlert').addClass('d-none');

            $.ajax({
                url: '{{ url("pre_orders") }}/' + id + '/convert',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    paying_method: method
                },
                success: function(resp) {
                    $('#btnConfirmDelivery').prop('disabled', false).html('<i class="fa fa-shopping-cart mr-1"></i> Deliver & Print Invoice');
                    if (resp && resp.success) {
                        $('#convertAlert').removeClass('d-none alert-danger').addClass('alert alert-success')
                            .html('<strong>' + resp.message + '</strong><br><a href="' + resp.invoice_url + '" target="_blank" class="btn btn-sm btn-dark mt-2 font-weight-bold"><i class="fa fa-print"></i> Print Official Invoice</a>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function(xhr) {
                    $('#btnConfirmDelivery').prop('disabled', false).html('<i class="fa fa-shopping-cart mr-1"></i> Deliver & Print Invoice');
                    var msg = 'Conversion failed.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#convertAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
