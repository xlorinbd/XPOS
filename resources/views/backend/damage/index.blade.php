@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-ban mr-2"></i> Damage / Defective Inventory</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Track damaged serials, write-offs, fault reasons, and dispatch to Vendor RMA.</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <button type="button" class="btn btn-danger font-weight-bold shadow-sm mr-2" data-toggle="modal" data-target="#logDamageModal">
                                <i class="fa fa-plus-circle mr-1"></i> Log Damaged Device
                            </button>
                            <a href="{{ route('supplier_rmas.index') }}" class="btn btn-warning font-weight-bold shadow-sm text-dark">
                                <i class="fa fa-truck mr-1"></i> Return to Vendor (RTV)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body bg-light border-bottom py-3 px-4">
                    <form method="GET" action="{{ route('damages.index') }}" class="form-inline">
                        <label class="mr-2 font-weight-bold small text-muted">Warehouse:</label>
                        <select name="warehouse_id" class="form-control form-control-sm mr-3" onchange="this.form.submit();">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </select>

                        <label class="mr-2 font-weight-bold small text-muted">Status:</label>
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit();">
                            <option value="">All Statuses</option>
                            <option value="logged" {{ $status === 'logged' ? 'selected' : '' }}>Logged / In Stock</option>
                            <option value="in_rma" {{ $status === 'in_rma' ? 'selected' : '' }}>In Vendor RMA</option>
                            <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="scrapped" {{ $status === 'scrapped' ? 'selected' : '' }}>Scrapped</option>
                        </select>
                    </form>
                </div>

                <!-- Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light" style="font-size:12px; text-transform:uppercase;">
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Serial Number</th>
                                    <th>Warehouse</th>
                                    <th>Reason / Fault</th>
                                    <th>Cost Value</th>
                                    <th>Responsible</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($damageRecords as $record)
                                @php
                                    $statusBadge = match($record->status) {
                                        'logged' => 'badge-danger',
                                        'in_rma' => 'badge-warning',
                                        'resolved' => 'badge-success',
                                        'scrapped' => 'badge-dark',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $record->created_at->format('d M Y') }}</td>
                                    <td>
                                        <strong>{{ $record->product ? $record->product->name : 'N/A' }}</strong>
                                        <small class="text-muted d-block">{{ $record->product ? $record->product->code : '' }}</small>
                                    </td>
                                    <td>
                                        <code class="text-primary font-weight-bold">{{ $record->serial_number }}</code>
                                    </td>
                                    <td>{{ $record->warehouse ? $record->warehouse->name : 'N/A' }}</td>
                                    <td>
                                        <strong>{{ $record->damage_reason }}</strong>
                                        @if($record->notes)
                                            <small class="text-muted d-block">{{ $record->notes }}</small>
                                        @endif
                                    </td>
                                    <td><strong>৳{{ number_format($record->damage_cost, 2) }}</strong></td>
                                    <td>{{ $record->responsible_person ?: '—' }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge }} px-2 py-1 font-weight-bold text-uppercase">
                                            {{ str_replace('_', ' ', $record->status) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        @if($record->status === 'logged')
                                            <a href="{{ route('supplier_rmas.create', ['serial' => $record->serial_number, 'damage_id' => $record->id]) }}" class="btn btn-sm btn-outline-warning font-weight-bold">
                                                <i class="fa fa-paper-plane mr-1"></i> Send to RMA
                                            </a>
                                        @elseif($record->rma)
                                            <a href="{{ route('supplier_rmas.index', ['status' => $record->rma->status]) }}" class="btn btn-sm btn-outline-info">
                                                {{ $record->rma->rma_no }}
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fa fa-shield fa-3x mb-2 d-block text-muted"></i>
                                        No damage records found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if ($damageRecords->hasPages())
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                    {{ $damageRecords->appends(['warehouse_id' => $warehouseId, 'status' => $status])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal: Log Damaged Device -->
<div class="modal fade" id="logDamageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header bg-danger text-white" style="border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-ban mr-2"></i> Log Damaged / Defective Device</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="logDamageForm" onsubmit="return false;">
                @csrf
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold small">Warehouse *</label>
                        <select id="damage_warehouse_id" name="warehouse_id" class="form-control" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ Auth::user()->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Select Available Serial Number *</label>
                        <select id="damage_serial_number" name="serial_number" class="form-control" required>
                            <option value="">-- Select Serial --</option>
                        </select>
                        <small class="text-muted">Only serials currently 'available' in stock can be marked damaged.</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Damage / Defect Reason *</label>
                        <select name="damage_reason" class="form-control" required>
                            <option value="Dead on Arrival (DOA)">Dead on Arrival (DOA)</option>
                            <option value="Display / Screen Defect">Display / Screen Defect</option>
                            <option value="Physical / Body Damage">Physical / Body Damage</option>
                            <option value="Liquid / Moisture Damage">Liquid / Moisture Damage</option>
                            <option value="Motherboard / Power Failure">Motherboard / Power Failure</option>
                            <option value="Customer Return (Defective)">Customer Return (Defective)</option>
                            <option value="Other Defect">Other Defect</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Responsible Person / Handler</label>
                        <input type="text" name="responsible_person" class="form-control" placeholder="e.g. Courier / Warehouse staff name">
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Additional Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Describe how or when the damage occurred..."></textarea>
                    </div>

                    <div id="damageAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitDamage" class="btn btn-danger font-weight-bold px-4">
                        <i class="fa fa-check mr-1"></i> Mark as Damaged
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
        function loadAvailableSerials(warehouseId) {
            $('#damage_serial_number').empty().append('<option value="">Loading serials...</option>');
            $.ajax({
                url: '{{ route("damages.available-serials") }}',
                data: { warehouse_id: warehouseId },
                success: function(serials) {
                    $('#damage_serial_number').empty().append('<option value="">-- Select Serial --</option>');
                    if (serials.length === 0) {
                        $('#damage_serial_number').append('<option value="" disabled>No available serials in this warehouse</option>');
                    } else {
                        serials.forEach(function(s) {
                            var prodName = s.product ? s.product.name : 'Device';
                            $('#damage_serial_number').append('<option value="' + s.serial_number + '">' + s.serial_number + ' (' + prodName + ' - Cost: ৳' + s.product.cost + ')</option>');
                        });
                    }
                }
            });
        }

        var initialWh = $('#damage_warehouse_id').val();
        if (initialWh) {
            loadAvailableSerials(initialWh);
        }

        $('#damage_warehouse_id').on('change', function() {
            loadAvailableSerials($(this).val());
        });

        $('#logDamageForm').on('submit', function(e) {
            e.preventDefault();
            $('#btnSubmitDamage').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Processing...');
            $('#damageAlert').addClass('d-none');

            $.ajax({
                url: '{{ route("damages.store") }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function(resp) {
                    $('#btnSubmitDamage').prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Mark as Damaged');
                    if (resp && resp.success) {
                        $('#damageAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                },
                error: function(xhr) {
                    $('#btnSubmitDamage').prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Mark as Damaged');
                    var msg = 'Failed to log damage.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#damageAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
