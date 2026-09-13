@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-truck mr-2"></i> Supplier RMA Details: #{{ $supplierRma->rma_number }}</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Return-to-Vendor tracking, supplier details, and warranty settlement.</p>
                        </div>
                        <a href="{{ route('supplier_rma.index') }}" class="btn btn-light btn-sm font-weight-bold mt-2 mt-md-0">
                            <i class="fa fa-arrow-left mr-1"></i> Back to RMA List
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light border p-3 h-100">
                                <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">RMA Status & Tracking</h6>
                                <p class="mb-1"><strong>Status:</strong> <span class="badge badge-info px-2 py-1 text-uppercase">{{ $supplierRma->status }}</span></p>
                                <p class="mb-1"><strong>Dispatched Date:</strong> {{ \Carbon\Carbon::parse($supplierRma->created_at)->format('d M Y, h:i A') }}</p>
                                <p class="mb-1"><strong>Courier / Tracking:</strong> {{ $supplierRma->tracking_number ?: 'Not specified' }}</p>
                                <p class="mb-0"><strong>Dispatched By:</strong> {{ $supplierRma->user->name ?? 'Staff' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border p-3 h-100">
                                <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Supplier / Vendor Information</h6>
                                <p class="mb-1"><strong>Supplier Name:</strong> {{ $supplierRma->supplier->name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Company:</strong> {{ $supplierRma->supplier->company_name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Phone:</strong> {{ $supplierRma->supplier->phone_number ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Warehouse Branch:</strong> {{ $supplierRma->warehouse->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border p-3 mb-4">
                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">Dispatched Defective Device</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Product:</strong> {{ $supplierRma->product->name ?? 'N/A' }} ({{ $supplierRma->product->code ?? '' }})</p>
                                <p class="mb-1"><strong>Serial / IMEI:</strong> <code class="text-danger font-weight-bold">{{ $supplierRma->serial->serial_number ?? ($supplierRma->serial_number ?? 'N/A') }}</code></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Reported Defect Reason:</strong> {{ $supplierRma->reason }}</p>
                                <p class="mb-1"><strong>Specific Purchase Unit Cost:</strong> <span class="text-dark font-weight-bold">৳{{ number_format($supplierRma->purchase_cost, 2) }}</span></p>
                            </div>
                        </div>
                    </div>

                    @if($supplierRma->resolution_notes)
                    <div class="alert alert-secondary mb-0">
                        <strong>Resolution Notes:</strong> {{ $supplierRma->resolution_notes }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
