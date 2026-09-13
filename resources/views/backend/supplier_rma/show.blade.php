@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>Supplier RMA Details: #{{ $supplierRma->rma_number }}</h4>
                        <a href="{{ route('supplier_rmas.index') }}" class="btn btn-default btn-sm">
                            <i class="dripicons-arrow-left"></i> Back to RMA List
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">RMA Status & Tracking</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Status:</strong></td>
                                            <td><span class="badge badge-info">{{ ucfirst($supplierRma->status) }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dispatched Date:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($supplierRma->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Courier / Tracking:</strong></td>
                                            <td>{{ $supplierRma->tracking_number ?: 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dispatched By:</strong></td>
                                            <td>{{ $supplierRma->user->name ?? 'Staff' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Supplier / Vendor Information</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Supplier Name:</strong></td>
                                            <td>{{ $supplierRma->supplier->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Company:</strong></td>
                                            <td>{{ $supplierRma->supplier->company_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td>{{ $supplierRma->supplier->phone_number ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Warehouse Branch:</strong></td>
                                            <td>{{ $supplierRma->warehouse->name ?? 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Dispatched Defective Device</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:50%;">
                                                <p><strong>Product:</strong> {{ $supplierRma->product->name ?? 'N/A' }} ({{ $supplierRma->product->code ?? '' }})</p>
                                                <p class="mb-0"><strong>Serial / IMEI:</strong> <code class="text-danger">{{ $supplierRma->serial->serial_number ?? ($supplierRma->serial_number ?? 'N/A') }}</code></p>
                                            </td>
                                            <td style="width:50%;">
                                                <p><strong>Defect Reason:</strong> {{ $supplierRma->reason }}</p>
                                                <p class="mb-0"><strong>Purchase Unit Cost:</strong> {{ number_format($supplierRma->purchase_cost, 2) }}</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if($supplierRma->resolution_notes)
                        <div class="alert alert-secondary mt-3 mb-0">
                            <strong>Resolution Notes:</strong> {{ $supplierRma->resolution_notes }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
