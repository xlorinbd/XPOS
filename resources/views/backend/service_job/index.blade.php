@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-wrench mr-2"></i> Warranty Claim & Service Tracking</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Log warranty claims, track RMA/repairs, assign technician notes, and handle customer delivery.</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="{{ route('service_jobs.create') }}" class="btn btn-warning font-weight-bold shadow-sm">
                                <i class="fa fa-plus-circle mr-1"></i> Open Service Ticket
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body bg-light border-bottom py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex align-items-center">
                            <span class="mr-2 font-weight-bold small text-muted">Filter Status:</span>
                            <select class="form-control form-control-sm font-weight-bold" onchange="location.href=this.value;" style="width:200px;">
                                <option value="{{ route('service_jobs.index') }}" {{ !$status ? 'selected' : '' }}>All Tickets</option>
                                <option value="{{ route('service_jobs.index', ['status' => 'received']) }}" {{ $status === 'received' ? 'selected' : '' }}>Received</option>
                                <option value="{{ route('service_jobs.index', ['status' => 'sent_to_lab']) }}" {{ $status === 'sent_to_lab' ? 'selected' : '' }}>Sent to Lab</option>
                                <option value="{{ route('service_jobs.index', ['status' => 'under_service']) }}" {{ $status === 'under_service' ? 'selected' : '' }}>Under Service</option>
                                <option value="{{ route('service_jobs.index', ['status' => 'ready_for_delivery']) }}" {{ $status === 'ready_for_delivery' ? 'selected' : '' }}>Ready for Delivery</option>
                                <option value="{{ route('service_jobs.index', ['status' => 'delivered']) }}" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered (Closed)</option>
                                <option value="{{ route('service_jobs.index', ['status' => 'rejected']) }}" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected (Returned)</option>
                                <option value="{{ route('service_jobs.index', ['status' => 'scrapped']) }}" {{ $status === 'scrapped' ? 'selected' : '' }}>Scrapped / Damaged</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light" style="font-size:12px; text-transform:uppercase;">
                                <tr>
                                    <th>Ticket No & Date</th>
                                    <th>Serial & Product</th>
                                    <th>Customer</th>
                                    <th>Problem & Condition</th>
                                    <th>Warranty</th>
                                    <th>Technician & Cost</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($serviceJobs as $job)
                                @php
                                    $statusBadge = match($job->status) {
                                        'received' => 'badge-secondary',
                                        'sent_to_lab' => 'badge-info',
                                        'under_service' => 'badge-primary',
                                        'ready_for_delivery' => 'badge-warning',
                                        'delivered' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'scrapped' => 'badge-dark',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong class="text-danger">{{ $job->ticket_no }}</strong>
                                        <small class="text-muted d-block">{{ $job->created_at->format('d M Y, h:i A') }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $job->product ? $job->product->name : 'N/A' }}</strong>
                                        <small class="text-info font-weight-bold d-block">
                                            <i class="fa fa-barcode"></i> <code>{{ $job->serial_number }}</code>
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ $job->customer_name }}</strong>
                                        <small class="text-muted d-block">{{ $job->customer_phone }}</small>
                                    </td>
                                    <td>
                                        <div class="small"><strong>Issue:</strong> {{ \Illuminate\Support\Str::limit($job->problem_description, 40) }}</div>
                                        @if($job->accessories_received)
                                            <small class="text-muted d-block"><strong>Acc:</strong> {{ $job->accessories_received }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($job->is_warranty_covered)
                                            <span class="badge badge-success"><i class="fa fa-shield"></i> Under Warranty</span>
                                        @else
                                            <span class="badge badge-secondary">Paid Service</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div><strong>Total: ৳{{ number_format($job->total_cost, 2) }}</strong></div>
                                        <small class="text-muted">Labor: ৳{{ number_format($job->service_charge, 2) }} | Parts: ৳{{ number_format($job->parts_charge, 2) }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusBadge }} px-2 py-1 font-weight-bold text-uppercase" style="font-size:11px;">
                                            {{ str_replace('_', ' ', $job->status) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                                Action
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="{{ route('service_jobs.print', $job->id) }}" target="_blank">
                                                    <i class="fa fa-print mr-1"></i> Print Token / Tag
                                                </a>

                                                @if(in_array($job->status, ['received', 'sent_to_lab', 'under_service']))
                                                    <a class="dropdown-item text-primary font-weight-bold btn-update-status" href="#" data-id="{{ $job->id }}" data-ticket="{{ $job->ticket_no }}" data-status="{{ $job->status }}" data-notes="{{ $job->technician_notes }}" data-service="{{ $job->service_charge }}" data-parts="{{ $job->parts_charge }}">
                                                        <i class="fa fa-pencil mr-1"></i> Update Status & Diagnosis
                                                    </a>
                                                @endif

                                                @if($job->status === 'ready_for_delivery')
                                                    <a class="dropdown-item text-success font-weight-bold btn-deliver-job" href="#" data-id="{{ $job->id }}" data-ticket="{{ $job->ticket_no }}" data-total="{{ $job->total_cost }}">
                                                        <i class="fa fa-check-circle mr-1"></i> Deliver to Customer
                                                    </a>
                                                @endif

                                                @if(!in_array($job->status, ['delivered', 'rejected', 'scrapped']))
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-warning btn-set-status" href="#" data-id="{{ $job->id }}" data-status="rejected">
                                                        <i class="fa fa-ban mr-1"></i> Reject Claim (Return As-Is)
                                                    </a>
                                                    <a class="dropdown-item text-danger btn-set-status" href="#" data-id="{{ $job->id }}" data-status="scrapped">
                                                        <i class="fa fa-trash mr-1"></i> Mark Scrapped / Dead
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa fa-wrench fa-3x mb-2 d-block text-muted"></i>
                                        No service tickets found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if ($serviceJobs->hasPages())
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                    {{ $serviceJobs->appends(['status' => $status])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal: Update Status & Diagnosis -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header bg-dark text-white" style="border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-stethoscope mr-2"></i> Update Service Ticket</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="updateStatusForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="edit_job_id">
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold small">Status *</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="sent_to_lab">Sent to Central Lab / Vendor</option>
                            <option value="under_service">Under Service (In Repair)</option>
                            <option value="ready_for_delivery">Ready for Delivery (Repair Completed)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Technician Diagnosis & Action Taken</label>
                        <textarea id="edit_technician_notes" name="technician_notes" class="form-control" rows="3" placeholder="Explain steps taken, components replaced, etc..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">Parts Charge (৳)</label>
                            <input type="number" id="edit_parts_charge" name="parts_charge" class="form-control" value="0" min="0" step="any">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small">Labor / Service Fee (৳)</label>
                            <input type="number" id="edit_service_charge" name="service_charge" class="form-control" value="0" min="0" step="any">
                        </div>
                    </div>

                    <div id="updateStatusAlert" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Close</button>
                    <button type="submit" id="btnSaveStatus" class="btn btn-primary font-weight-bold px-4">
                        <i class="fa fa-save mr-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Deliver to Customer -->
<div class="modal fade" id="deliverModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header bg-success text-white" style="border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-check-circle mr-2"></i> Deliver Device to Customer</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="deliverForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="deliver_job_id">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded border mb-3">
                        <h5 class="mb-1 font-weight-bold" id="deliver_ticket_no"></h5>
                        <div class="d-flex justify-content-between font-weight-bold" style="font-size:16px;">
                            <span>Total Bill to Collect:</span>
                            <span class="text-danger" id="deliver_total_cost"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Payment Method</label>
                        <select id="deliver_paying_method" name="paying_method" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="Card">Credit / Debit Card</option>
                            <option value="Bkash">Bkash / Nagad</option>
                            <option value="None">Free (Covered by Warranty)</option>
                        </select>
                    </div>

                    <div id="deliverAlert" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Close</button>
                    <button type="submit" id="btnConfirmDelivery" class="btn btn-success font-weight-bold px-4">
                        <i class="fa fa-handshake-o mr-1"></i> Confirm Delivery & Close
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
        // Edit Status & Diagnosis Modal
        $('.btn-update-status').on('click', function(e) {
            e.preventDefault();
            $('#edit_job_id').val($(this).data('id'));
            $('#edit_status').val($(this).data('status'));
            $('#edit_technician_notes').val($(this).data('notes') || '');
            $('#edit_service_charge').val($(this).data('service') || 0);
            $('#edit_parts_charge').val($(this).data('parts') || 0);
            $('#updateStatusAlert').addClass('d-none');
            $('#updateStatusModal').modal('show');
        });

        $('#updateStatusForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#edit_job_id').val();
            $('#btnSaveStatus').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Saving...');

            $.ajax({
                url: '{{ url("service_jobs") }}/' + id + '/status',
                type: 'POST',
                data: $(this).serialize(),
                success: function(resp) {
                    $('#btnSaveStatus').prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save Changes');
                    if (resp && resp.success) {
                        $('#updateStatusAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                },
                error: function(xhr) {
                    $('#btnSaveStatus').prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save Changes');
                    var msg = 'Failed to update ticket.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#updateStatusAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });

        // Simple direct status updates (reject, scrap)
        $('.btn-set-status').on('click', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var status = $(this).data('status');
            var confirmMsg = 'Are you sure you want to mark this ticket as ' + status.toUpperCase() + '?';

            if (!confirm(confirmMsg)) return;

            $.ajax({
                url: '{{ url("service_jobs") }}/' + id + '/status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
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

        // Deliver to customer
        $('.btn-deliver-job').on('click', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var ticket = $(this).data('ticket');
            var total = parseFloat($(this).data('total')) || 0;

            $('#deliver_job_id').val(id);
            $('#deliver_ticket_no').text(ticket);
            $('#deliver_total_cost').text('৳ ' + total.toFixed(2));
            $('#deliverAlert').addClass('d-none');
            $('#deliverModal').modal('show');
        });

        $('#deliverForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#deliver_job_id').val();
            $('#btnConfirmDelivery').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Delivering...');

            $.ajax({
                url: '{{ url("service_jobs") }}/' + id + '/deliver',
                type: 'POST',
                data: $(this).serialize(),
                success: function(resp) {
                    $('#btnConfirmDelivery').prop('disabled', false).html('<i class="fa fa-handshake-o mr-1"></i> Confirm Delivery & Close');
                    if (resp && resp.success) {
                        $('#deliverAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                },
                error: function(xhr) {
                    $('#btnConfirmDelivery').prop('disabled', false).html('<i class="fa fa-handshake-o mr-1"></i> Confirm Delivery & Close');
                    var msg = 'Failed to deliver.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#deliverAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
