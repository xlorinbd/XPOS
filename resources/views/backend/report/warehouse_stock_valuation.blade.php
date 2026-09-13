@extends('backend.layout.main') @section('content')

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h4 class="text-center"><i class="fa fa-line-chart mr-2"></i> Multi-Warehouse Stock & Asset Valuation Report</h4>
            </div>
            <form method="GET" action="{{ route('report.warehouse_stock_valuation') }}" class="mt-4 mb-3">
                <div class="col-md-6 offset-md-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>Select Warehouse:</strong> &nbsp;</label>
                        <div class="d-tc flex-grow-1">
                            <div class="input-group">
                                <select name="warehouse_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">{{ __('db.submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Metric Boxes (Native Theme) -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card text-center p-3 border-left border-primary shadow-sm">
                    <span class="text-muted small text-uppercase font-weight-bold">Salable Stock Value</span>
                    <h3 class="font-weight-bold text-primary mb-0 mt-1">৳{{ number_format($grandSalableValue, 2) }}</h3>
                    <small class="text-muted">Available on floor (Specific ID + WAC)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3 border-left border-warning shadow-sm">
                    <span class="text-muted small text-uppercase font-weight-bold">In-Transit Value</span>
                    <h3 class="font-weight-bold text-warning mb-0 mt-1">৳{{ number_format($grandInTransitValue, 2) }}</h3>
                    <small class="text-muted">Inter-branch transfers in transit</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3 border-left border-danger shadow-sm">
                    <span class="text-muted small text-uppercase font-weight-bold">In-Store Damaged</span>
                    <h3 class="font-weight-bold text-danger mb-0 mt-1">৳{{ number_format($grandDamagedValue, 2) }}</h3>
                    <small class="text-muted">Damaged / Defective inventory</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3 border-left border-info shadow-sm">
                    <span class="text-muted small text-uppercase font-weight-bold">Pending Vendor RMA</span>
                    <h3 class="font-weight-bold text-info mb-0 mt-1">৳{{ number_format($grandPendingRmaValue, 2) }}</h3>
                    <small class="text-muted">Dispatched to vendor (Receivable)</small>
                </div>
            </div>
        </div>

        <!-- Grand Total Banner -->
        <div class="card bg-dark text-white p-3 mb-4 rounded">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="mb-0 font-weight-bold"><i class="fa fa-cubes mr-2"></i> Total Enterprise Inventory Asset Valuation</h5>
                    <small class="text-white-50">Sum of Salable + In-Transit + In-Store Damaged + Pending Vendor RMA</small>
                </div>
                <h2 class="font-weight-bold text-success mb-0">৳{{ number_format($grandTotalValue, 2) }}</h2>
            </div>
        </div>

        <!-- Breakdown Table -->
        <div class="card p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Warehouse / Branch</th>
                            <th>Available Serials</th>
                            <th>Serialized Salable (৳)</th>
                            <th>Accessories / Non-Serial (৳)</th>
                            <th>Total Salable (৳)</th>
                            <th>In-Transit Value (৳)</th>
                            <th>Damaged Value (৳)</th>
                            <th>Pending Vendor RMA (৳)</th>
                            <th class="text-right">Total Branch Asset (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($valuationData as $data)
                        <tr>
                            <td>
                                <strong>{{ $data['warehouse']->name }}</strong>
                                <small class="text-muted d-block">{{ $data['warehouse']->address ?? '' }}</small>
                            </td>
                            <td><span class="badge badge-primary px-2 py-1">{{ $data['available_serials_count'] }} pcs</span></td>
                            <td>৳{{ number_format($data['serialized_salable_value'], 2) }}</td>
                            <td>৳{{ number_format($data['non_serialized_salable_value'], 2) }}</td>
                            <td><strong class="text-primary">৳{{ number_format($data['total_salable_value'], 2) }}</strong></td>
                            <td>৳{{ number_format($data['in_transit_value'], 2) }}</td>
                            <td>৳{{ number_format($data['damaged_value'], 2) }}</td>
                            <td>৳{{ number_format($data['pending_rma_value'], 2) }}</td>
                            <td class="text-right"><strong class="text-success" style="font-size:15px;">৳{{ number_format($data['total_valuation'], 2) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No warehouse data available.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="thead-dark font-weight-bold">
                        <tr>
                            <th>Total Summary:</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>৳{{ number_format($grandSalableValue, 2) }}</th>
                            <th>৳{{ number_format($grandInTransitValue, 2) }}</th>
                            <th>৳{{ number_format($grandDamagedValue, 2) }}</th>
                            <th>৳{{ number_format($grandPendingRmaValue, 2) }}</th>
                            <th class="text-right text-success" style="font-size:16px;">৳{{ number_format($grandTotalValue, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>

@endsection
