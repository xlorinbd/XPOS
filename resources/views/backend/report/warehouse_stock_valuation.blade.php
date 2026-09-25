@extends('backend.layout.main')
@section('content')

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{ __('db.Warehouse Stock & Asset Valuation Report') }}</h3>
            </div>
            {!! Form::open(['route' => 'report.warehouse_stock_valuation', 'method' => 'get', 'id' => 'report-form']) !!}
            <div class="row mb-3">
                <div class="col-md-6 offset-md-3 mt-3 text-center">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{ __('db.Choose Warehouse') }}</strong> &nbsp;</label>
                        <div class="d-tc flex-grow-1">
                            <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true">
                                <option value="">{{ __('db.All Warehouse') }}</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-tc ml-2">
                            <button class="btn btn-primary" type="submit">{{ __('db.submit') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>

    <!-- Summary Counts (Native SalePro Theme) -->
    <div class="container-fluid">
        <div class="dashboard-counts pt-0">
            <div class="row">
                <div class="col-md-3">
                    <div class="wrapper count-title text-center">
                        <div class="icon"><i class="fa fa-cubes" style="color: #111111"></i></div>
                        <div>
                            <div class="count-number">{{ number_format($grandSalableValue, $general_setting->decimal ?? 2, '.', '') }}</div>
                            <div class="name"><strong style="color: #111111">{{ __('db.Salable Stock Value') }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="wrapper count-title text-center">
                        <div class="icon"><i class="fa fa-truck" style="color: #ffc107"></i></div>
                        <div>
                            <div class="count-number">{{ number_format($grandInTransitValue, $general_setting->decimal ?? 2, '.', '') }}</div>
                            <div class="name"><strong style="color: #ffc107">{{ __('db.In-Transit Value') }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="wrapper count-title text-center">
                        <div class="icon"><i class="fa fa-exclamation-triangle" style="color: #ff7588"></i></div>
                        <div>
                            <div class="count-number">{{ number_format($grandDamagedValue, $general_setting->decimal ?? 2, '.', '') }}</div>
                            <div class="name"><strong style="color: #ff7588">{{ __('db.In-Store Damaged') }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="wrapper count-title text-center">
                        <div class="icon"><i class="fa fa-wrench" style="color: #17a2b8"></i></div>
                        <div>
                            <div class="count-number">{{ number_format($grandPendingRmaValue, $general_setting->decimal ?? 2, '.', '') }}</div>
                            <div class="name"><strong style="color: #17a2b8">{{ __('db.Pending Vendor RMA') }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Enterprise Inventory Asset Valuation (Native SalePro Card) -->
    <div class="container-fluid">
        <div class="card mt-2 mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-1" style="color: #555;"><i class="fa fa-building text-primary mr-2"></i> <strong>{{ __('db.Total Enterprise Inventory Asset Valuation') }}</strong></h4>
                        <p class="text-muted mb-0 small">Sum of Salable + In-Transit + In-Store Damaged + Pending Vendor RMA</p>
                    </div>
                    <div class="col-md-4 text-md-right mt-2 mt-md-0">
                        <span class="text-muted d-block small">Total Valuation</span>
                        <h2 class="text-primary mb-0 font-weight-bold">{{ number_format($grandTotalValue, $general_setting->decimal ?? 2, '.', '') }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Table (Native SalePro Table) -->
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive mb-4">
                    <table id="report-table" class="table table-hover" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="not-exported"></th>
                                <th>{{ __('db.Warehouse') }}</th>
                                <th>{{ __('db.Available Serials') }}</th>
                                <th>{{ __('db.Serialized Salable') }}</th>
                                <th>{{ __('db.Accessories / Non-Serial') }}</th>
                                <th>{{ __('db.Total Salable') }}</th>
                                <th>{{ __('db.In-Transit Value') }}</th>
                                <th>{{ __('db.Damaged Value') }}</th>
                                <th>{{ __('db.Pending Vendor RMA') }}</th>
                                <th>{{ __('db.Total Asset') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($valuationData as $key => $data)
                            <tr>
                                <td>{{ $key }}</td>
                                <td>
                                    <strong>{{ $data['warehouse']->name }}</strong>
                                    @if(!empty($data['warehouse']->address))
                                        <br><small class="text-muted">{{ $data['warehouse']->address }}</small>
                                    @endif
                                </td>
                                <td><span class="badge badge-primary">{{ $data['available_serials_count'] }} pcs</span></td>
                                <td>{{ number_format($data['serialized_salable_value'], $general_setting->decimal ?? 2, '.', '') }}</td>
                                <td>{{ number_format($data['non_serialized_salable_value'], $general_setting->decimal ?? 2, '.', '') }}</td>
                                <td><strong>{{ number_format($data['total_salable_value'], $general_setting->decimal ?? 2, '.', '') }}</strong></td>
                                <td>{{ number_format($data['in_transit_value'], $general_setting->decimal ?? 2, '.', '') }}</td>
                                <td>{{ number_format($data['damaged_value'], $general_setting->decimal ?? 2, '.', '') }}</td>
                                <td>{{ number_format($data['pending_rma_value'], $general_setting->decimal ?? 2, '.', '') }}</td>
                                <td><strong>{{ number_format($data['total_valuation'], $general_setting->decimal ?? 2, '.', '') }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="tfoot active">
                            <tr>
                                <th></th>
                                <th>{{ __('db.Total') }}</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th>{{ number_format($grandSalableValue, $general_setting->decimal ?? 2, '.', '') }}</th>
                                <th>{{ number_format($grandInTransitValue, $general_setting->decimal ?? 2, '.', '') }}</th>
                                <th>{{ number_format($grandDamagedValue, $general_setting->decimal ?? 2, '.', '') }}</th>
                                <th>{{ number_format($grandPendingRmaValue, $general_setting->decimal ?? 2, '.', '') }}</th>
                                <th>{{ number_format($grandTotalValue, $general_setting->decimal ?? 2, '.', '') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #warehouse-stock-valuation-menu").addClass("active");

    $('#report-table').DataTable( {
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
                'targets': 0
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }
                    return data;
                },
                'checkboxes': {
                    'selectRow': true,
                    'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            }
        ]
    } );
</script>
@endpush
