@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-plus-circle mr-2"></i> Create New Pre-Order Booking</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Book a device from another branch or reserve stock for a customer with advance deposit.</p>
                        </div>
                        <a href="{{ route('pre_orders.index') }}" class="btn btn-light btn-sm font-weight-bold mt-2 mt-md-0">
                            <i class="fa fa-list mr-1"></i> View All Pre-Orders
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div id="preOrderAlert" class="alert d-none font-weight-bold mb-4"></div>

                    <form id="createPreOrderForm">
                        @csrf

                        <!-- Section 1: Customer Information -->
                        <div class="bg-light p-3 rounded mb-4 border">
                            <h5 class="font-weight-bold text-dark border-bottom pb-2 mb-3">
                                <i class="fa fa-user mr-1 text-primary"></i> Customer Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Customer Name *</label>
                                    <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Enter customer full name" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Customer Phone / Mobile *</label>
                                    <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="01XXXXXXXXX" required>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Product & Branch Assignment -->
                        <div class="bg-light p-3 rounded mb-4 border">
                            <h5 class="font-weight-bold text-dark border-bottom pb-2 mb-3">
                                <i class="fa fa-laptop mr-1 text-primary"></i> Product & Inventory Location
                            </h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Select Product *</label>
                                    <select name="product_id" id="pre_product_id" class="form-control" required>
                                        <option value="">-- Choose Product --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->price }}" data-floor="{{ $p->last_border_price ?? 0 }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                                {{ $p->name }} [{{ $p->code }}] - Retail: ৳{{ number_format($p->price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small id="productFloorNotice" class="text-muted d-block mt-1"></small>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Source Branch (Where Stock is Located) *</label>
                                    <select name="from_warehouse_id" id="from_warehouse_id" class="form-control" required>
                                        <option value="">-- Select Source Branch --</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ $selectedFromWarehouseId == $wh->id ? 'selected' : '' }}>
                                                {{ $wh->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Destination Branch (Customer Pickup Branch) *</label>
                                    <select name="to_warehouse_id" id="to_warehouse_id" class="form-control" required>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ $defaultWarehouseId == $wh->id ? 'selected' : '' }}>
                                                {{ $wh->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Specific Serial Number / IMEI (Optional)</label>
                                    <input type="text" name="serial_number" id="serial_number" class="form-control" placeholder="Leave blank for any available unit" value="{{ $selectedSerial ?? '' }}">
                                    <small class="text-muted">If specified, this serial will be locked into 'reserved' state at the source branch.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Pricing & Advance Deposit -->
                        <div class="bg-light p-3 rounded mb-4 border">
                            <h5 class="font-weight-bold text-dark border-bottom pb-2 mb-3">
                                <i class="fa fa-money mr-1 text-primary"></i> Pricing & Payment Terms
                            </h5>
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold small">Agreed Selling Price (৳) *</label>
                                    <input type="number" step="0.01" name="price" id="pre_price" class="form-control font-weight-bold" placeholder="0.00" required>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold small">Advance Deposit Received (৳)</label>
                                    <input type="number" step="0.01" name="advance_amount" id="advance_amount" class="form-control font-weight-bold text-success" value="0.00">
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold small">Advance Payment Method</label>
                                    <select name="paying_method" id="paying_method" class="form-control">
                                        <option value="Cash">Cash</option>
                                        <option value="Card">Card</option>
                                        <option value="Bkash">Bkash / Mobile Banking</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Expected Delivery Date</label>
                                    <input type="date" name="expected_delivery_date" class="form-control" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Order Notes / Customer Request</label>
                                    <input type="text" name="notes" class="form-control" placeholder="Special customer specifications or courier instructions">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('pre_orders.index') }}" class="btn btn-secondary font-weight-bold px-4">
                                <i class="fa fa-arrow-left mr-1"></i> Cancel
                            </a>
                            <button type="submit" id="btnSubmitPreOrder" class="btn btn-primary btn-lg font-weight-bold px-5 shadow-sm">
                                <i class="fa fa-check mr-1"></i> Confirm Pre-Order Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    $('#pre_product_id').on('change', function() {
        var opt = $(this).find(':selected');
        var price = opt.data('price') || 0;
        var floor = opt.data('floor') || 0;

        if (price > 0 && !$('#pre_price').val()) {
            $('#pre_price').val(price);
        }

        if (floor > 0) {
            $('#productFloorNotice').html('<span class="text-danger font-weight-bold"><i class="fa fa-shield"></i> Border Floor: ৳' + parseFloat(floor).toLocaleString() + '</span> (Cannot sell below this price)');
        } else {
            $('#productFloorNotice').text('');
        }
    });

    $('#createPreOrderForm').on('submit', function(e) {
        e.preventDefault();

        var btn = $('#btnSubmitPreOrder');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Processing...');
        $('#preOrderAlert').addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: "{{ route('pre_orders.store') }}",
            type: "POST",
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(resp) {
                btn.prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Confirm Pre-Order Booking');
                if (resp.success) {
                    $('#preOrderAlert').removeClass('d-none').addClass('alert-success')
                        .html('<i class="fa fa-check-circle mr-1"></i> ' + resp.message + ' Redirecting to list...');
                    setTimeout(function() {
                        window.location.href = "{{ route('pre_orders.index') }}";
                    }, 1200);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Confirm Pre-Order Booking');
                var msg = 'An error occurred while creating pre-order.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#preOrderAlert').removeClass('d-none').addClass('alert-danger')
                    .html('<i class="fa fa-exclamation-triangle mr-1"></i> ' + msg);
            }
        });
    });
});
</script>
@endpush
