@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
                <div class="card-body p-4 text-white" style="border-radius:12px 12px 0 0; background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%) !important;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-exchange mr-2"></i> Device Exchange & Replacement Engine</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Atomically return old serial, issue replacement unit, and settle price differential in one unified transaction.</p>
                        </div>
                        <a href="{{ url('warranty/check') }}" class="btn btn-outline-light btn-sm font-weight-bold">
                            <i class="fa fa-shield mr-1"></i> Warranty Check
                        </a>
                    </div>
                </div>

                <div class="card-body p-4 bg-white">
                    <form id="exchangeForm" onsubmit="return false;">
                        @csrf
                        <div class="row">
                            <!-- Left Column: Old Device Return -->
                            <div class="col-md-6 border-right pr-md-4">
                                <h5 class="font-weight-bold text-danger mb-3 border-bottom pb-2">
                                    <i class="fa fa-arrow-circle-down mr-1"></i> 1. Old Device Being Returned
                                </h5>

                                <div class="form-group">
                                    <label class="font-weight-bold">Returned Serial Number *</label>
                                    <input type="text" id="old_serial_number" name="old_serial_number" class="form-control font-weight-bold" value="{{ $oldSerial ? $oldSerial->serial_number : '' }}" placeholder="Scan or enter sold serial number..." required>
                                </div>

                                @if ($oldSerial && $oldSerial->product)
                                <div class="p-3 bg-light rounded border mb-3">
                                    <div class="font-weight-bold text-primary mb-1">{{ $oldSerial->product->name }}</div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="fa fa-laptop"></i> {{ $oldSerial->product->processor }} | {{ $oldSerial->product->ram }} | {{ $oldSerial->product->storage }}
                                    </small>
                                    <small class="text-dark d-block">
                                        <strong>Original Sale Ref:</strong> {{ $originalSale ? $originalSale->reference_no : 'N/A' }}
                                    </small>
                                </div>
                                @endif

                                <div class="form-group">
                                    <label class="font-weight-bold">Device Condition / Return Action *</label>
                                    <select name="return_action" id="return_action" class="form-control" required>
                                        <option value="damaged" selected>Defective / Hardware Issue (Send to RMA/Vendor)</option>
                                        <option value="restock">Resellable / Good Condition (Restock to Salable Inventory)</option>
                                    </select>
                                    <small class="form-text text-muted">Defective devices will NOT be added to salable stock.</small>
                                </div>

                                <div class="form-group">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="font-weight-bold mb-0">
                                            Return / Trade-In Credit Value (৳) *
                                            <i id="tradeLockIcon" class="fa fa-lock text-muted ml-1" title="Locked to original sale price"></i>
                                        </label>
                                        @if (\Auth::user()->role_id <= 2)
                                            <button type="button" id="btnUnlockTradeIn" class="btn btn-outline-warning btn-xs py-0 px-2 font-weight-bold" style="font-size:11px;">
                                                <i class="fa fa-unlock-alt mr-1"></i> Manager Override
                                            </button>
                                        @endif
                                    </div>
                                    <input type="number" id="trade_in_value" name="trade_in_value" class="form-control font-weight-bold text-success" value="{{ $tradeInValue }}" step="any" min="0" required readonly style="background:#f8f9fa; font-size:16px;">
                                    <small id="tradeInHelp" class="form-text text-muted">Locked to original sale price (৳{{ number_format($tradeInValue, 2) }}).</small>
                                </div>
                            </div>

                            <!-- Right Column: Replacement Device -->
                            <div class="col-md-6 pl-md-4">
                                <h5 class="font-weight-bold text-success mb-3 border-bottom pb-2">
                                    <i class="fa fa-arrow-circle-up mr-1"></i> 2. Replacement Device Issued
                                </h5>

                                <div class="form-group">
                                    <label class="font-weight-bold">Branch / Warehouse *</label>
                                    <select name="warehouse_id" id="warehouse_id" class="form-control" required>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ (isset($oldSerial) && $oldSerial->warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Replacement Product *</label>
                                    <select id="new_product_id" name="new_product_id" class="form-control selectpicker" data-live-search="true" title="Search replacement laptop/gadget..." required>
                                        @php
                                            $allGadgets = \App\Models\Product::where('is_active', true)->where('is_imei', true)->get();
                                        @endphp
                                        @foreach ($allGadgets as $g)
                                            <option value="{{ $g->id }}" data-price="{{ $g->price }}" data-floor="{{ $g->last_border_price ?? 0 }}">
                                                {{ $g->name }} (৳{{ number_format($g->price, 0) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Select Available Serial Number *</label>
                                    <select name="new_serial_number" id="new_serial_number" class="form-control" required>
                                        <option value="">-- Choose available serial --</option>
                                    </select>
                                    <small id="serialHelpText" class="form-text text-muted">Available serials in selected branch will appear here.</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Selling Price of Replacement Device (৳) *</label>
                                    <input type="number" id="new_price" name="new_price" class="form-control font-weight-bold" step="any" min="0" placeholder="0.00" required style="font-size:16px;">
                                    <div id="borderFloorNotice" class="d-none mt-1">
                                        <small class="text-danger font-weight-bold"><i class="fa fa-shield"></i> Border Floor: ৳<span id="floorVal">0</span> (Cannot sell below this)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settlement Banner -->
                        <div class="card mt-4 border shadow-sm" style="border-radius:10px; background:#fdfdfd;">
                            <div class="card-body p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <div id="settlementBox">
                                            <h5 class="mb-1 font-weight-bold text-dark">Differential Settlement:</h5>
                                            <h3 id="settlementText" class="font-weight-bold mb-0 text-primary">৳ 0.00 (Even Swap)</h3>
                                            <small id="settlementDetail" class="text-muted"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-5 mt-3 mt-md-0 text-md-right">
                                        <div id="paymentMethodSection" class="form-group mb-2 d-none text-left">
                                            <label class="font-weight-bold small">Payment Method for Extra Amount *</label>
                                            <select name="paying_method" id="paying_method" class="form-control form-control-sm">
                                                <option value="Cash">Cash</option>
                                                <option value="Credit Card">Card</option>
                                                <option value="Bkash">Bkash / MFS</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                            </select>
                                        </div>
                                        <button type="submit" id="btnSubmitExchange" class="btn btn-success btn-lg px-4 font-weight-bold shadow-sm">
                                            <i class="fa fa-check-circle mr-1"></i> Complete Exchange
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="exchangeAlert" class="mt-3 d-none"></div>
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
        var currentFloor = 0;

        function recalcSettlement() {
            var tradeIn = parseFloat($('#trade_in_value').val()) || 0;
            var newP = parseFloat($('#new_price').val()) || 0;
            var diff = roundToTwo(newP - tradeIn);

            if (currentFloor > 0 && newP < currentFloor) {
                $('#borderFloorNotice').removeClass('d-none');
                $('#floorVal').text(currentFloor.toFixed(2));
            } else {
                $('#borderFloorNotice').addClass('d-none');
            }

            if (diff > 0) {
                $('#settlementText').removeClass('text-success text-muted').addClass('text-danger')
                    .text('Customer Pays: ৳ ' + diff.toFixed(2));
                $('#settlementDetail').text('Replacement device price (৳' + newP.toFixed(2) + ') is higher than trade-in credit (৳' + tradeIn.toFixed(2) + ')');
                $('#paymentMethodSection').removeClass('d-none');
            } else if (diff < 0) {
                $('#settlementText').removeClass('text-danger text-muted').addClass('text-success')
                    .text('Refund to Customer: ৳ ' + Math.abs(diff).toFixed(2));
                $('#settlementDetail').text('Replacement device price (৳' + newP.toFixed(2) + ') is lower than trade-in credit (৳' + tradeIn.toFixed(2) + ')');
                $('#paymentMethodSection').addClass('d-none');
            } else {
                $('#settlementText').removeClass('text-danger text-success').addClass('text-primary')
                    .text('৳ 0.00 (Even Replacement Swap)');
                $('#settlementDetail').text('Straight 1-to-1 warranty replacement with no monetary adjustment.');
                $('#paymentMethodSection').addClass('d-none');
            }
        }

        function roundToTwo(num) {
            return +(Math.round(num + "e+2")  + "e-2");
        }

        $('#new_product_id').on('change', function() {
            var pid = $(this).val();
            var opt = $(this).find('option:selected');
            var price = parseFloat(opt.data('price')) || 0;
            currentFloor = parseFloat(opt.data('floor')) || 0;

            $('#new_price').val(price.toFixed(2));
            recalcSettlement();

            var wid = $('#warehouse_id').val();
            if (pid && wid) {
                loadAvailableSerials(pid, wid);
            }
        });

        $('#warehouse_id').on('change', function() {
            var pid = $('#new_product_id').val();
            var wid = $(this).val();
            if (pid && wid) {
                loadAvailableSerials(pid, wid);
            }
        });

        $('#new_price, #trade_in_value').on('input', function() {
            recalcSettlement();
        });

        $('#btnUnlockTradeIn').on('click', function() {
            if (confirm('Enable Manager Override to adjust trade-in value (e.g. for depreciation or condition deductions)?')) {
                $('#trade_in_value').prop('readonly', false).css('background', '#fff').focus();
                $('#tradeLockIcon').removeClass('fa-lock text-muted').addClass('fa-unlock text-warning');
                $('#tradeInHelp').html('<span class="text-warning font-weight-bold"><i class="fa fa-exclamation-triangle"></i> Manager Override Active: Adjusted trade-in value will be recorded.</span>');
                $(this).hide();
            }
        });

        function loadAvailableSerials(pid, wid) {
            $('#new_serial_number').empty().append('<option value="">Loading serials...</option>');
            $.ajax({
                url: '{{ url("/exchange/serials") }}',
                type: 'GET',
                data: { product_id: pid, warehouse_id: wid },
                success: function(data) {
                    $('#new_serial_number').empty().append('<option value="">-- Choose available serial --</option>');
                    if (data && data.length > 0) {
                        data.forEach(function(s) {
                            var cond = s.detailed_condition ? ' (' + s.detailed_condition + ')' : '';
                            $('#new_serial_number').append('<option value="' + s.serial_number + '">' + s.serial_number + cond + '</option>');
                        });
                        $('#serialHelpText').text(data.length + ' available serial(s) found in this branch.');
                    } else {
                        $('#serialHelpText').text('No available serials in this branch for this product!');
                    }
                }
            });
        }

        $('#exchangeForm').on('submit', function(e) {
            e.preventDefault();

            var newP = parseFloat($('#new_price').val()) || 0;
            if (currentFloor > 0 && newP < currentFloor) {
                alert('Price violation: Selling price cannot be below minimum border floor (৳' + currentFloor.toFixed(2) + ').');
                return;
            }

            $('#btnSubmitExchange').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Processing Exchange...');
            $('#exchangeAlert').addClass('d-none');

            $.ajax({
                url: '{{ url("/exchange/store") }}',
                type: 'POST',
                data: $('#exchangeForm').serialize(),
                success: function(resp) {
                    $('#btnSubmitExchange').prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Complete Exchange');
                    if (resp && resp.success) {
                        $('#exchangeAlert').removeClass('d-none alert-danger').addClass('alert alert-success')
                            .html('<strong><i class="fa fa-check-circle"></i> ' + resp.message + '</strong><br><a href="' + resp.invoice_url + '" target="_blank" class="btn btn-sm btn-dark mt-2 font-weight-bold"><i class="fa fa-print"></i> Print New Sale Invoice</a>');
                        
                        // Disable form to prevent resubmission
                        $('#exchangeForm input, #exchangeForm select, #exchangeForm button').prop('disabled', true);
                    } else {
                        alert(resp.error || 'Exchange failed.');
                    }
                },
                error: function(xhr) {
                    $('#btnSubmitExchange').prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> Complete Exchange');
                    var errMsg = 'Exchange failed!';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errMsg = xhr.responseJSON.error;
                    }
                    $('#exchangeAlert').removeClass('d-none alert-success').addClass('alert alert-danger')
                        .html('<i class="fa fa-exclamation-triangle"></i> ' + errMsg);
                }
            });
        });

        // Trigger initial calculation
        recalcSettlement();
    });
</script>
@endpush
