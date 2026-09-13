@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{ __('db.Return to Vendor (RTV) / RMA Tracking') }}</h3>
            </div>
            {!! Form::open(['route' => 'supplier_rmas.index', 'method' => 'get']) !!}
            <div class="row mb-3">
                <div class="col-md-4 offset-md-2 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{ __('db.Supplier') }}</strong> &nbsp;</label>
                        <div class="d-tc flex-grow-1">
                            <select name="supplier_id" class="selectpicker form-control" data-live-search="true" onchange="this.form.submit();">
                                <option value="">{{ __('db.All') }}</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ $supplierId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{ __('db.status') }}</strong> &nbsp;</label>
                        <div class="d-tc flex-grow-1">
                            <select name="status" class="selectpicker form-control" onchange="this.form.submit();">
                                <option value="">{{ __('db.All') }}</option>
                                <option value="dispatched" {{ $status === 'dispatched' ? 'selected' : '' }}>Dispatched (At Vendor)</option>
                                <option value="replaced" {{ $status === 'replaced' ? 'selected' : '' }}>Replaced</option>
                                <option value="refunded" {{ $status === 'refunded' ? 'selected' : '' }}>Refunded / Credited</option>
                                <option value="rejected_returned" {{ $status === 'rejected_returned' ? 'selected' : '' }}>Rejected & Returned</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>

        <div class="mb-3">
            <a href="{{ route('supplier_rmas.create') }}" class="btn btn-info">
                <i class="dripicons-plus"></i> {{ __('db.New Vendor RMA') }}
            </a>
            <a href="{{ route('damages.index') }}" class="btn btn-primary ml-2">
                <i class="fa fa-ban"></i> {{ __('db.Damaged List') }}
            </a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="rma-table" class="table table-hover" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="not-exported"></th>
                                <th>{{ __('db.RMA No') }}</th>
                                <th>{{ __('db.date') }}</th>
                                <th>{{ __('db.product') }}</th>
                                <th>{{ __('db.Serial Number') }}</th>
                                <th>{{ __('db.Supplier') }}</th>
                                <th>{{ __('db.Warehouse') }}</th>
                                <th>{{ __('db.Cost / Claim') }}</th>
                                <th>{{ __('db.Resolution') }}</th>
                                <th>{{ __('db.status') }}</th>
                                <th class="not-exported">{{ __('db.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rmas as $key => $rma)
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
                                <td>{{ $key }}</td>
                                <td><strong>{{ $rma->rma_no }}</strong></td>
                                <td>{{ $rma->dispatched_at ? \Carbon\Carbon::parse($rma->dispatched_at)->format('d M Y') : '' }}</td>
                                <td>
                                    <strong>{{ $rma->product ? $rma->product->name : 'N/A' }}</strong>
                                </td>
                                <td>
                                    <code>{{ $rma->serial_number }}</code>
                                </td>
                                <td>{{ $rma->supplier ? $rma->supplier->name : 'N/A' }}</td>
                                <td>{{ $rma->warehouse ? $rma->warehouse->name : '' }}</td>
                                <td>{{ number_format($rma->purchase_cost, 2) }}</td>
                                <td>
                                    @if($rma->status === 'replaced')
                                        <span class="text-success font-weight-bold">
                                            Replaced: <code>{{ $rma->replacement_serial_number }}</code>
                                        </span>
                                    @elseif($rma->status === 'refunded')
                                        <span class="text-info font-weight-bold">
                                            Refund: {{ number_format($rma->refund_amount, 2) }}
                                        </span>
                                    @elseif($rma->status === 'rejected_returned')
                                        <span class="text-danger font-weight-bold">
                                            Written off as Loss
                                        </span>
                                    @else
                                        <span class="text-muted small">Pending Response</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $badge }}">
                                        {{ str_replace('_', ' ', $rma->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($rma->status === 'dispatched')
                                        <button type="button" class="btn btn-sm btn-primary btn-resolve-rma" data-id="{{ $rma->id }}" data-rmano="{{ $rma->rma_no }}" data-cost="{{ $rma->purchase_cost }}" data-serial="{{ $rma->serial_number }}">
                                            <i class="dripicons-document-edit"></i> {{ __('db.Resolve') }}
                                        </button>
                                    @else
                                        <span class="text-muted small">Closed</span>
                                    @endif
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

<!-- Modal: Resolve RMA -->
<div class="modal fade" id="resolveRmaModal" tabindex="-1" role="dialog" aria-labelledby="resolveRmaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="resolveRmaModalLabel" class="modal-title">{{ __('db.Resolve Vendor RMA') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="resolveRmaForm" onsubmit="return false;">
                @csrf
                <input type="hidden" id="resolve_rma_id">
                <div class="modal-body">
                    <div class="card p-3 mb-3 bg-light">
                        <h6 class="mb-1" id="resolve_rma_no"></h6>
                        <div class="d-flex justify-content-between small">
                            <span>Dispatched Serial:</span>
                            <strong id="resolve_dispatched_sn"></strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>Original Purchase Cost:</span>
                            <strong id="resolve_cost"></strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Resolution Type') }} *</label>
                        <select id="resolution_type" name="resolution_type" class="form-control selectpicker" required>
                            <option value="replaced">Unit Replacement (Supplier provided new serial)</option>
                            <option value="refunded">Credit / Refund (Supplier refunded or credited account)</option>
                            <option value="rejected_returned">Rejected (Supplier rejected, written off as loss)</option>
                        </select>
                    </div>

                    <!-- Replacement Details -->
                    <div id="div_replacement" class="form-group">
                        <label>{{ __('db.New Replacement Serial Number') }} *</label>
                        <input type="text" id="replacement_serial_number" name="replacement_serial_number" class="form-control" placeholder="Scan or enter new serial...">
                        <small class="text-muted">{{ __('db.This serial will be added to warehouse stock as Available.') }}</small>
                    </div>

                    <!-- Refund Details -->
                    <div id="div_refund" class="form-group d-none">
                        <label>{{ __('db.Refund Amount') }} *</label>
                        <input type="number" id="refund_amount" name="refund_amount" class="form-control" value="0" min="0" step="any">
                        <small class="text-muted">{{ __('db.Any difference between cost and refund will be recorded as a loss expense.') }}</small>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Note') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Notes from supplier or RMA resolution..."></textarea>
                    </div>

                    <div id="resolveAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.close') }}</button>
                    <button type="submit" id="btnSubmitResolve" class="btn btn-primary">
                        {{ __('db.submit') }}
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
        $('#rma-table').DataTable({
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
            $('#resolve_cost').text(cost.toFixed(2));
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
                    $('#btnSubmitResolve').prop('disabled', false).html('{{ __("db.submit") }}');
                    if (resp && resp.success) {
                        $('#resolveAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                },
                error: function(xhr) {
                    $('#btnSubmitResolve').prop('disabled', false).html('{{ __("db.submit") }}');
                    var msg = 'Resolution failed.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#resolveAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
