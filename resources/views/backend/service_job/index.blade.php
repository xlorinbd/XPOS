@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{ __('db.Warranty Claim & Service Tracking') }}</h3>
            </div>
            {!! Form::open(['route' => 'service_jobs.index', 'method' => 'get']) !!}
            <div class="row mb-3">
                <div class="col-md-4 offset-md-4 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{ __('db.status') }}</strong> &nbsp;</label>
                        <div class="d-tc flex-grow-1">
                            <select name="status" class="selectpicker form-control" onchange="this.form.submit();">
                                <option value="">{{ __('db.All') }}</option>
                                <option value="received" {{ $status === 'received' ? 'selected' : '' }}>Received</option>
                                <option value="sent_to_lab" {{ $status === 'sent_to_lab' ? 'selected' : '' }}>Sent to Lab</option>
                                <option value="under_service" {{ $status === 'under_service' ? 'selected' : '' }}>Under Service</option>
                                <option value="ready_for_delivery" {{ $status === 'ready_for_delivery' ? 'selected' : '' }}>Ready for Delivery</option>
                                <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered (Closed)</option>
                                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected (Returned)</option>
                                <option value="scrapped" {{ $status === 'scrapped' ? 'selected' : '' }}>Scrapped / Damaged</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>

        <div class="mb-3">
            <a href="{{ route('service_jobs.create') }}" class="btn btn-info">
                <i class="dripicons-plus"></i> {{ __('db.Open Service Ticket') }}
            </a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="service-table" class="table table-hover" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="not-exported"></th>
                                <th>{{ __('db.Ticket No') }}</th>
                                <th>{{ __('db.date') }}</th>
                                <th>{{ __('db.product') }}</th>
                                <th>{{ __('db.Serial Number') }}</th>
                                <th>{{ __('db.customer') }}</th>
                                <th>{{ __('db.Problem') }}</th>
                                <th>{{ __('db.Warranty') }}</th>
                                <th>{{ __('db.Cost') }}</th>
                                <th>{{ __('db.status') }}</th>
                                <th class="not-exported">{{ __('db.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($serviceJobs as $key => $job)
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
                                <td>{{ $key }}</td>
                                <td><strong>{{ $job->ticket_no }}</strong></td>
                                <td>{{ $job->created_at->format('d M Y') }}</td>
                                <td>
                                    <strong>{{ $job->product ? $job->product->name : 'N/A' }}</strong>
                                </td>
                                <td>
                                    <code>{{ $job->serial_number }}</code>
                                </td>
                                <td>
                                    {{ $job->customer_name }}
                                    <small class="text-muted d-block">{{ $job->customer_phone }}</small>
                                </td>
                                <td>
                                    {{ \Illuminate\Support\Str::limit($job->problem_description, 35) }}
                                </td>
                                <td>
                                    @if($job->is_warranty_covered)
                                        <span class="badge badge-success">{{ __('db.Under Warranty') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ __('db.Paid Service') }}</span>
                                    @endif
                                </td>
                                <td>{{ number_format($job->total_cost, 2) }}</td>
                                <td>
                                    <span class="badge {{ $statusBadge }}">
                                        {{ str_replace('_', ' ', $job->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ __('db.action') }}
                                            <span class="caret"></span>
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                            <li>
                                                <a class="btn btn-link" href="{{ route('service_jobs.print', $job->id) }}" target="_blank">
                                                    <i class="dripicons-print"></i> {{ __('db.Print Token') }}
                                                </a>
                                            </li>

                                            @if(in_array($job->status, ['received', 'sent_to_lab', 'under_service']))
                                            <li>
                                                <button type="button" class="btn btn-link btn-update-status" data-id="{{ $job->id }}" data-ticket="{{ $job->ticket_no }}" data-status="{{ $job->status }}" data-notes="{{ $job->technician_notes }}" data-service="{{ $job->service_charge }}" data-parts="{{ $job->parts_charge }}">
                                                    <i class="dripicons-document-edit"></i> {{ __('db.Update Status') }}
                                                </button>
                                            </li>
                                            @endif

                                            @if($job->status === 'ready_for_delivery')
                                            <li>
                                                <button type="button" class="btn btn-link btn-deliver-job" data-id="{{ $job->id }}" data-ticket="{{ $job->ticket_no }}" data-total="{{ $job->total_cost }}">
                                                    <i class="dripicons-checkmark"></i> {{ __('db.Deliver to Customer') }}
                                                </button>
                                            </li>
                                            @endif

                                            @if(!in_array($job->status, ['delivered', 'rejected', 'scrapped']))
                                            <li class="divider"></li>
                                            <li>
                                                <button type="button" class="btn btn-link btn-set-status text-warning" data-id="{{ $job->id }}" data-status="rejected">
                                                    <i class="dripicons-cross"></i> {{ __('db.Reject Claim') }}
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="btn btn-link btn-set-status text-danger" data-id="{{ $job->id }}" data-status="scrapped">
                                                    <i class="dripicons-trash"></i> {{ __('db.Mark Scrapped') }}
                                                </button>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal: Update Status & Diagnosis -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="updateStatusModalLabel" class="modal-title">{{ __('db.Update Service Ticket') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="updateStatusForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="edit_job_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('db.status') }} *</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="sent_to_lab">Sent to Central Lab / Vendor</option>
                            <option value="under_service">Under Service (In Repair)</option>
                            <option value="ready_for_delivery">Ready for Delivery (Repair Completed)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Technician Diagnosis & Action Taken') }}</label>
                        <textarea id="edit_technician_notes" name="technician_notes" class="form-control" rows="3" placeholder="Explain steps taken, components replaced, etc..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Parts Charge') }}</label>
                            <input type="number" id="edit_parts_charge" name="parts_charge" class="form-control" value="0" min="0" step="any">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Service Charge') }}</label>
                            <input type="number" id="edit_service_charge" name="service_charge" class="form-control" value="0" min="0" step="any">
                        </div>
                    </div>

                    <div id="updateStatusAlert" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.close') }}</button>
                    <button type="submit" id="btnSaveStatus" class="btn btn-primary">
                        {{ __('db.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Deliver to Customer -->
<div class="modal fade" id="deliverModal" tabindex="-1" role="dialog" aria-labelledby="deliverModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="deliverModalLabel" class="modal-title">{{ __('db.Deliver Device to Customer') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="deliverForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="deliver_job_id">
                <div class="modal-body">
                    <div class="card p-3 mb-3 bg-light">
                        <h5 class="mb-1" id="deliver_ticket_no"></h5>
                        <div class="d-flex justify-content-between">
                            <strong>{{ __('db.Total Bill to Collect') }}:</strong>
                            <strong class="text-primary" id="deliver_total_cost"></strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Payment Method') }} *</label>
                        <select id="deliver_paying_method" name="paying_method" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="Card">Credit / Debit Card</option>
                            <option value="Bkash">Bkash / Nagad</option>
                            <option value="None">Free (Covered by Warranty)</option>
                        </select>
                    </div>

                    <div id="deliverAlert" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.close') }}</button>
                    <button type="submit" id="btnConfirmDelivery" class="btn btn-primary">
                        {{ __('db.Confirm') }}
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
        $('#service-table').DataTable({
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{__("db.records per page")}}',
                "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search":  '{{__("db.Search")}}',
                'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
                }
            },
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, -1]
                }
            ],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="dripicons-document-new"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="fa fa-print"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="fa fa-eye"></i>',
                    columns: ':gt(0)'
                }
            ]
        });

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
                    $('#btnSaveStatus').prop('disabled', false).html('{{ __("db.submit") }}');
                    if (resp && resp.success) {
                        $('#updateStatusAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                },
                error: function(xhr) {
                    $('#btnSaveStatus').prop('disabled', false).html('{{ __("db.submit") }}');
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
            $('#deliver_total_cost').text(total.toFixed(2));
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
                    $('#btnConfirmDelivery').prop('disabled', false).html('{{ __("db.Confirm") }}');
                    if (resp && resp.success) {
                        $('#deliverAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                },
                error: function(xhr) {
                    $('#btnConfirmDelivery').prop('disabled', false).html('{{ __("db.Confirm") }}');
                    var msg = 'Failed to deliver.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#deliverAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
