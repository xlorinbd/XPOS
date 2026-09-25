@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>Create Pre-Order Booking</h4>
                        <a href="{{ route('pre_orders.index') }}" class="btn btn-info btn-sm">
                            <i class="dripicons-list"></i> Pre-Order List
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        
                        <div id="preOrderAlert" class="alert d-none"></div>

                        <form id="createPreOrderForm">
                            @csrf

                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <h5><strong>Customer Information</strong></h5>
                                    <hr>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Customer Name *</label>
                                    <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Enter customer name" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Customer Phone / Mobile *</label>
                                    <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="01XXXXXXXXX" required>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12 mb-2">
                                    <h5><strong>Product & Branch Assignment</strong></h5>
                                    <hr>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Select Product *</label>
                                    <select name="product_id" id="pre_product_id" class="form-control selectpicker" data-live-search="true" title="Select product..." required>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->price }}" data-floor="{{ $p->last_border_price ?? 0 }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                                {{ $p->name }} [{{ $p->code }}] - Retail: {{ amount_format($p->price) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small id="productFloorNotice" class="text-danger d-block mt-1 font-weight-bold"></small>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>Source Branch (Stock Location) *</label>
                                    <select name="from_warehouse_id" id="from_warehouse_id" class="form-control selectpicker" data-live-search="true" title="Select source branch..." required>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ $selectedFromWarehouseId == $wh->id ? 'selected' : '' }}>
                                                {{ $wh->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>Destination Branch (Customer Pickup Location) *</label>
                                    <select name="to_warehouse_id" id="to_warehouse_id" class="form-control selectpicker" data-live-search="true" title="Select destination branch..." required>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ $defaultWarehouseId == $wh->id ? 'selected' : '' }}>
                                                {{ $wh->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>Specific Serial Number (Optional)</label>
                                    <input type="text" name="serial_number" id="serial_number" class="form-control" placeholder="Leave blank for any available unit" value="{{ $selectedSerial ?? '' }}">
                                    <small class="text-muted">If specified, this serial will be reserved at source branch.</small>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12 mb-2">
                                    <h5><strong>Pricing & Advance Deposit</strong></h5>
                                    <hr>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Agreed Selling Price *</label>
                                    <input type="number" step="0.01" name="price" id="pre_price" class="form-control" placeholder="0.00" required>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>Advance Deposit * (required)</label>
                                    <input type="number" step="0.01" min="1" name="advance_amount" id="advance_amount" class="form-control" placeholder="0.00" required>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>Advance Payment Method *</label>
                                    <select name="paying_method" id="paying_method" class="form-control">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Mobile Banking">Mobile Banking</option>
                                        <option value="Card">Card</option>
                                    </select>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label>Goes into account</label>
                                    <select name="advance_account_id" id="advance_account_id" class="form-control"></select>
                                </div>

                                <div class="col-md-12">
                                    <small id="kindHint" class="text-muted"></small>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>Expected Delivery Date</label>
                                    <input type="date" name="expected_delivery_date" class="form-control" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>Order Notes / Customer Request</label>
                                    <input type="text" name="notes" class="form-control" placeholder="Special customer specifications...">
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <button type="submit" id="btnSubmitPreOrder" class="btn btn-primary">
                                    {{ __('db.submit') }}
                                </button>
                                <a href="{{ route('pre_orders.index') }}" class="btn btn-secondary">
                                    {{ __('db.Cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
var kgAccounts = {!! \App\Models\Account::where('is_active', true)->get(['id', 'name', 'type', 'warehouse_id'])->toJson() !!};
$(document).ready(function() {
    function refreshAccountsAndKind() {
        kgFillAccounts($('#advance_account_id'), kgAccounts, $('#to_warehouse_id').val(), $('#paying_method').val());
        var same = $('#from_warehouse_id').val() && String($('#from_warehouse_id').val()) === String($('#to_warehouse_id').val());
        $('#kindHint').text(same ? 'Same branch: this is a Pre-Booking. The unit stays here, set aside for the customer.' : 'Different branch: this is a Pre-Order. The unit is sent from the source branch (items still in transit from abroad cannot be pre-ordered).');
    }
    $('#to_warehouse_id, #from_warehouse_id, #paying_method').on('change changed.bs.select', refreshAccountsAndKind);
    setTimeout(refreshAccountsAndKind, 300);

    $('#pre_product_id').on('change', function() {
        var opt = $(this).find(':selected');
        var price = opt.data('price') || 0;
        var floor = opt.data('floor') || 0;

        if (price > 0 && !$('#pre_price').val()) {
            $('#pre_price').val(price);
        }

        if (floor > 0) {
            $('#productFloorNotice').text('Border Floor: ' + parseFloat(floor).toLocaleString() + ' (Selling below this needs a reason)');
        } else {
            $('#productFloorNotice').text('');
        }
    });

    $('#createPreOrderForm').on('submit', function(e) {
        e.preventDefault();

        var btn = $('#btnSubmitPreOrder');
        btn.prop('disabled', true).text('Processing...');
        $('#preOrderAlert').addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: "{{ route('pre_orders.store') }}",
            type: "POST",
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(resp) {
                btn.prop('disabled', false).text("{{ __('db.submit') }}");
                if (resp.success) {
                    $('#preOrderAlert').removeClass('d-none').addClass('alert alert-success')
                        .text(resp.message + ' Redirecting...');
                    setTimeout(function() {
                        window.location.href = "{{ route('pre_orders.index') }}";
                    }, 1000);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text("{{ __('db.submit') }}");
                if (xhr.responseJSON && xhr.responseJSON.needs_border_reason) {
                    kgBorderReason(xhr.responseJSON.lines, function (reason) {
                        $('#createPreOrderForm input[name="border_price_reason"]').remove();
                        $('#createPreOrderForm').append($('<input type="hidden" name="border_price_reason">').val(reason)).trigger('submit');
                    });
                    return;
                }
                var msg = 'An error occurred while creating pre-order.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#preOrderAlert').removeClass('d-none').addClass('alert alert-danger').text(msg);
            }
        });
    });
});
</script>
@endpush
