@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>Return to Vendor (RTV) RMA Dispatch</h4>
                        <a href="{{ route('supplier_rmas.index') }}" class="btn btn-info btn-sm">
                            <i class="dripicons-list"></i> All RMAs
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>

                        <div id="rmaAlert" class="alert d-none"></div>

                        <form id="rmaForm" onsubmit="return false;">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Supplier / Vendor *</label>
                                    <select name="supplier_id" class="form-control selectpicker" data-live-search="true" title="Select supplier..." required>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->company_name ?? 'Vendor' }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>Warehouse (Stock Location) *</label>
                                    <select id="rma_warehouse_id" name="warehouse_id" class="form-control selectpicker" data-live-search="true" title="Select warehouse..." required>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ Auth::user()->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Select Damaged Serial Number *</label>
                                <select id="rma_serial_number" name="serial_number" class="form-control" required>
                                    <option value="">Loading damaged serials...</option>
                                </select>
                                <small class="text-muted">Only serials currently marked as 'Damaged' in this warehouse can be dispatched.</small>
                            </div>

                            <div class="form-group">
                                <label>Defect Reason / Return Cause *</label>
                                <input type="text" name="reason" class="form-control" placeholder="e.g. Display backlight dead, no boot, motherboard shorted..." required>
                            </div>

                            <div class="form-group">
                                <label>Courier / Tracking Number</label>
                                <input type="text" name="tracking_number" class="form-control" placeholder="e.g. Steadfast / SA Paribahan Consignment #">
                            </div>

                            <div class="form-group">
                                <label>Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Special RMA instructions..."></textarea>
                            </div>

                            <div class="form-group mt-3">
                                <button type="submit" id="btnSubmitRma" class="btn btn-primary">
                                    <i class="dripicons-export"></i> Dispatch RMA to Vendor
                                </button>
                                <a href="{{ route('supplier_rmas.index') }}" class="btn btn-secondary">
                                    {{trans('file.Cancel')}}
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
    $(document).ready(function() {
        var preselectedSerial = "{{ request('serial') }}";

        function loadDamagedSerials(warehouseId) {
            $('#rma_serial_number').empty().append('<option value="">Loading damaged serials...</option>');
            $.ajax({
                url: '{{ route("supplier_rmas.damaged-serials") }}',
                data: { warehouse_id: warehouseId },
                success: function(serials) {
                    $('#rma_serial_number').empty().append('<option value="">-- Select Damaged Serial --</option>');
                    if (serials.length === 0) {
                        $('#rma_serial_number').append('<option value="" disabled>No damaged serials in this warehouse</option>');
                    } else {
                        serials.forEach(function(s) {
                            var isSelected = (preselectedSerial && preselectedSerial === s.serial_number) ? 'selected' : '';
                            $('#rma_serial_number').append('<option value="' + s.serial_number + '" ' + isSelected + '>' + s.serial_number + ' (' + s.product_name + ' - Cost: ' + s.purchase_cost + ')</option>');
                        });
                    }
                }
            });
        }

        var initialWh = $('#rma_warehouse_id').val();
        if (initialWh) {
            loadDamagedSerials(initialWh);
        }

        $('#rma_warehouse_id').on('change', function() {
            loadDamagedSerials($(this).val());
        });

        $('#rmaForm').on('submit', function(e) {
            e.preventDefault();
            $('#btnSubmitRma').prop('disabled', true).text('Dispatching...');
            $('#rmaAlert').addClass('d-none');

            $.ajax({
                url: '{{ route("supplier_rmas.store") }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function(resp) {
                    $('#btnSubmitRma').prop('disabled', false).text('Dispatch RMA to Vendor');
                    if (resp && resp.success) {
                        $('#rmaAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() {
                            window.location.href = '{{ route("supplier_rmas.index") }}';
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    $('#btnSubmitRma').prop('disabled', false).text('Dispatch RMA to Vendor');
                    var msg = 'Failed to dispatch RMA.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#rmaAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
