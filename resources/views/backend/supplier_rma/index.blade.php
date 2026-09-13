@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-truck mr-2"></i> Return to Vendor (RTV) / RMA Tracking</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Track defective items sent to suppliers, process replacements, and reconcile credit notes / refunds.</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="{{ route('damages.index') }}" class="btn btn-outline-light font-weight-bold mr-2">
                                <i class="fa fa-ban mr-1"></i> Damaged List
                            </a>
                            <a href="{{ route('supplier_rmas.create') }}" class="btn btn-warning font-weight-bold shadow-sm text-dark">
                                <i class="fa fa-paper-plane mr-1"></i> New Vendor RMA Dispatch
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body bg-light border-bottom py-3 px-4">
                    <form method="GET" action="{{ route('supplier_rmas.index') }}" class="form-inline">
                        <label class="mr-2 font-weight-bold small text-muted">Status:</label>
                        <select name="status" class="form-control form-control-sm mr-3" onchange="this.form.submit();">
                            <option value="">All Statuses</option>
                            <option value="dispatched" {{ $status === 'dispatched' ? 'selected' : '' }}>Dispatched (At Vendor)</option>
                            <option value="replaced" {{ $status === 'replaced' ? 'selected' : '' }}>Replaced</option>
                            <option value="refunded" {{ $status === 'refunded' ? 'selected' : '' }}>Refunded / Credited</option>
                            <option value="rejected_returned" {{ $status === 'rejected_returned' ? 'selected' : '' }}>Rejected & Returned</option>
                        </select>

                        <label class="mr-2 font-weight-bold small text-muted">Supplier:</label>
                        <select name="supplier_id" class="form-control form-control-sm mr-3" onchange="this.form.submit();">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ $supplierId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <!-- Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light" style="font-size:12px; text-transform:uppercase;">
                                <tr>
                                    <th>RMA No & Date</th>
                                    <th>Product & Serial</th>
                                    <th>Supplier</th>
                                    <th>Defect / Reason</th>
                                    <th>Cost / Claim</th>
                                    <th>Resolution</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rmas as $rma)
                                @php
                                    $badge = match($rma->status) {
                                        'dispatched' => 'badge-warning',
                                        'replaced' => 'badge-success',
                                        'refunded' => 'badge-info',
                                        'rejected_returned' => 'badge-danger',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="text-primary">{{ $rma->rma_no }}</strong>
                                        <small class="text-muted d-block">{{ $rma->dispatched_at ? \Carbon\Carbon::parse($rma->dispatched_at)->format('d M Y') : '' }}</small>
                                        @if($rma->tracking_number)
                                            <span class="badge badge-light border mt-1">Track: {{ $rma->tracking_number }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $rma->product ? $rma->product->name : 'N/A' }}</strong>
                                        <small class="text-info font-weight-bold d-block">
                                            <i class="fa fa-barcode"></i> <code>{{ $rma->serial_number }}</code>
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ $rma->supplier ? $rma->supplier->name : 'N/A' }}</strong>
                                        <small class="text-muted d-block">{{ $rma->warehouse ? $rma->warehouse->name : '' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $rma->reason }}</div>
                                        @if($rma->notes)
                                            <small class="text-muted">{{ $rma->notes }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div><strong>Cost:</strong> ৳{{ number_format($rma->purchase_cost, 2) }}</div>
                                        @if($rma->loss_amount > 0)
                                            <small class="text-danger font-weight-bold d-block">Loss: ৳{{ number_format($rma->loss_amount, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($rma->status === 'replaced')
                                            <span class="small text-success font-weight-bold">
                                                <i class="fa fa-check"></i> Replaced:<br><code>{{ $rma->replacement_serial_number }}</code>
                                            </span>
                                        @elseif($rma->status === 'refunded')
                                            <span class="small text-info font-weight-bold">
                                                <i class="fa fa-money"></i> Refund: ৳{{ number_format($rma->refund_amount, 2) }}
                                            </span>
                                        @elseif($rma->status === 'rejected_returned')
                                            <span class="small text-danger font-weight-bold">
                                                <i class="fa fa-times"></i> Written off as Loss
                                            </span>
                                        @else
                                            <span class="text-muted small">Pending Response</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $badge }} px-2 py-1 font-weight-bold text-uppercase">
                                            {{ str_replace('_', ' ', $rma->status) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        @if($rma->status === 'dispatched')
                                            <button type="button" class="btn btn-sm btn-primary font-weight-bold btn-resolve-rma" data-id="{{ $rma->id }}" data-rmano="{{ $rma->rma_no }}" data-cost="{{ $rma->purchase_cost }}" data-serial="{{ $rma->serial_number }}">
                                                <i class="fa fa-gavel mr-1"></i> Resolve
                                            </button>
                                        @else
                                            <span class="text-muted small">Closed</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa fa-truck fa-3x mb-2 d-block text-muted"></i>
                                        No Vendor RMA tickets found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if ($rmas->hasPages())
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                    {{ $rmas->appends(['status' => $status, 'warehouse_id' => $warehouseId, 'supplier_id' => $supplierId])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal: Resolve RMA -->
<div class="modal fade" id="resolveRmaModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header bg-dark text-white" style="border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-gavel mr-2"></i> Resolve Vendor RMA</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="resolveRmaForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="resolve_rma_id">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded border mb-3">
                        <h6 class="mb-1 font-weight-bold" id="resolve_rma_no"></h6>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Dispatched Serial:</span>
                            <strong id="resolve_dispatched_sn" class="text-dark"></strong>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Original Purchase Cost:</span>
                            <strong id="resolve_cost" class="text-dark"></strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Resolution Type *</label>
                        <select id="resolution_type" name="resolution_type" class="form-control" required>
                            <option value="replaced">Unit Replacement (Supplier provided new serial)</option>
                            <option value="refunded">Credit / Refund (Supplier refunded or credited account)</option>
                            <option value="rejected_returned">Rejected (Supplier rejected, written off as loss)</option>
                        </select>
                    </div>

                    <!-- Replacement Details -->
                    <div id="div_replacement" class="form-group">
                        <label class="font-weight-bold small text-success">New Replacement Serial Number / IMEI *</label>
                        <input type="text" id="replacement_serial_number" name="replacement_serial_number" class="form-control" placeholder="Scan or enter new serial...">
                        <small class="text-muted">This serial will be added to warehouse stock as 'Available'.</small>
                    </div>

                    <!-- Refund Details -->
                    <div id="div_refund" class="form-group d-none">
                        <label class="font-weight-bold small text-info">Refund / Credit Amount (৳) *</label>
                        <input type="number" id="refund_amount" name="refund_amount" class="form-control font-weight-bold" value="0" min="0" step="any">
                        <small class="text-muted">Any difference between cost and refund will be recorded as a loss expense.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Resolution Remarks</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Notes from supplier or RMA resolution..."></textarea>
                    </div>

                    <div id="resolveAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Close</button>
                    <button type="submit" id="btnSubmitResolve" class="btn btn-primary font-weight-bold px-4">
                        <i class="fa fa-check mr-1"></i> Save Resolution
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
        $('#resolution_type').on('change', function() {
            var val = $(this).val();
            if (val === 'replaced') {
                $('#div_replacement').removeClass('d-none');
                $('#div_refund').addClass('d-none');
            } else if (val === 'refunded') {
                $('#div_replacement').addClass('d-none');
                $('#div_refund').removeClass('d-none');
            } else {
                $('#div_replacement').addClass('d-none');
                $('#div_refund').addClass('d-none');
            }
        });

        $('.btn-resolve-rma').on('click', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var rmano = $(this).data('rmano');
            var cost = parseFloat($(this).data('cost')) || 0;
            var sn = $(this).data('serial');

            $('#resolve_rma_id').val(id);
            $('#resolve_rma_no').text(rmano);
            $('#resolve_dispatched_sn').text(sn);
            $('#resolve_cost').text('৳ ' + cost.toFixed(2));
            $('#refund_amount').val(cost.toFixed(2));
            $('#resolveAlert').addClass('d-none');
            $('#resolveRmaModal').modal('show');
        });

        $('#resolveRmaForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#resolve_rma_id').val();
            $('#btnSubmitResolve').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Processing...');

            $.ajax({
                url: '{{ url("supplier_rmas") }}/' + id + '/resolve',
                type: 'POST',
                data: $(this).serialize(),
                success: function(resp) {
                    $('#btnSubmitResolve').prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Save Resolution');
                    if (resp && resp.success) {
                        $('#resolveAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                },
                error: function(xhr) {
                    $('#btnSubmitResolve').prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Save Resolution');
                    var msg = 'Resolution failed.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#resolveAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
