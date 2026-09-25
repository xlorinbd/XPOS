@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{ __('db.Damage / Defective Inventory') }}</h3>
            </div>
            {!! Form::open(['route' => 'damages.index', 'method' => 'get']) !!}
            <div class="row mb-3">
                <div class="col-md-4 offset-md-2 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{ __('db.Warehouse') }}</strong> &nbsp;</label>
                        <div class="d-tc flex-grow-1">
                            <select name="warehouse_id" class="selectpicker form-control" data-live-search="true" onchange="this.form.submit();">
                                <option value="">{{ __('db.All Warehouse') }}</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
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
                                <option value="logged" {{ $status === 'logged' ? 'selected' : '' }}>Logged / In Stock</option>
                                <option value="in_rma" {{ $status === 'in_rma' ? 'selected' : '' }}>In Vendor RMA</option>
                                <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="scrapped" {{ $status === 'scrapped' ? 'selected' : '' }}>Scrapped</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>

        <div class="mb-3">
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#logDamageModal">
                <i class="dripicons-plus"></i> {{ __('db.Log Damaged Device') }}
            </button>
            <a href="{{ route('supplier_rmas.index') }}" class="btn btn-primary ml-2">
                <i class="fa fa-truck"></i> {{ __('db.Return to Vendor (RTV)') }}
            </a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="damage-table" class="table table-hover" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="not-exported"></th>
                                <th>{{ __('db.date') }}</th>
                                <th>{{ __('db.product') }}</th>
                                <th>{{ __('db.Serial Number') }}</th>
                                <th>{{ __('db.Warehouse') }}</th>
                                <th>{{ __('db.Reason / Fault') }}</th>
                                <th>{{ __('db.Cost Value') }}</th>
                                <th>{{ __('db.Responsible') }}</th>
                                <th>{{ __('db.status') }}</th>
                                <th class="not-exported">{{ __('db.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($damageRecords as $key => $record)
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
                                <td>{{ $key }}</td>
                                <td>{{ $record->created_at->format('d M Y') }}</td>
                                <td>
                                    <strong>{{ $record->product ? $record->product->name : 'N/A' }}</strong>
                                    <small class="text-muted d-block">{{ $record->product ? $record->product->code : '' }}</small>
                                </td>
                                <td>
                                    <code>{{ $record->serial_number }}</code>
                                </td>
                                <td>{{ $record->warehouse ? $record->warehouse->name : 'N/A' }}</td>
                                <td>
                                    <strong>{{ $record->damage_reason }}</strong>
                                    @if($record->notes)
                                        <small class="text-muted d-block">{{ $record->notes }}</small>
                                    @endif
                                </td>
                                <td>{{ amount_format($record->damage_cost) }}</td>
                                <td>{{ $record->responsible_person ?: '—' }}</td>
                                <td>
                                    <span class="badge {{ $statusBadge }}">
                                        {{ str_replace('_', ' ', $record->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($record->status === 'logged')
                                        <a href="{{ route('supplier_rmas.create', ['serial' => $record->serial_number, 'damage_id' => $record->id]) }}" class="btn btn-sm btn-outline-warning">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal: Log Damaged Device -->
<div class="modal fade" id="logDamageModal" tabindex="-1" role="dialog" aria-labelledby="logDamageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="logDamageModalLabel" class="modal-title">{{ __('db.Log Damaged Device') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <form id="logDamageForm" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('db.Warehouse') }} *</label>
                        <select id="damage_warehouse_id" name="warehouse_id" class="form-control selectpicker" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ Auth::user()->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Select Available Serial Number') }} *</label>
                        <select id="damage_serial_number" name="serial_number" class="form-control" required>
                            <option value="">-- {{ __('db.Select') }} --</option>
                        </select>
                        <small class="text-muted">{{ __('db.Only serials currently available in stock can be marked damaged.') }}</small>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Reason / Fault') }} *</label>
                        <select name="damage_reason" class="form-control selectpicker" required>
                            @foreach(\App\Models\Lookup::ofType('damage_type')->active()->orderBy('name')->get() as $kgDt)
                                <option value="{{ $kgDt->name }}">{{ $kgDt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Responsible Person') }}</label>
                        <input type="text" name="responsible_person" class="form-control" placeholder="e.g. Courier / Warehouse staff name">
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Note') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Describe how or when the damage occurred..."></textarea>
                    </div>

                    <div id="damageAlert" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.close') }}</button>
                    <button type="submit" id="btnSubmitDamage" class="btn btn-primary">
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
        $('#damage-table').DataTable({
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
                            $('#damage_serial_number').append('<option value="' + s.serial_number + '">' + s.serial_number + ' (' + prodName + ' - Cost: ' + s.product.cost + ')</option>');
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
                    $('#btnSubmitDamage').prop('disabled', false).html('{{ __("db.submit") }}');
                    if (resp && resp.success) {
                        $('#damageAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(resp.message);
                        setTimeout(function() { location.reload(); }, 1200);
                    }
                },
                error: function(xhr) {
                    $('#btnSubmitDamage').prop('disabled', false).html('{{ __("db.submit") }}');
                    var msg = 'Failed to log damage.';
                    if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                    $('#damageAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
                }
            });
        });
    });
</script>
@endpush
