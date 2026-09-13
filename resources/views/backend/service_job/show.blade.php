@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-ticket mr-2"></i> Service Job Ticket: {{ $serviceJob->job_number }}</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Service diagnosis, technician tracking, and RMA status.</p>
                        </div>
                        <div>
                            <a href="{{ route('service_jobs.printToken', $serviceJob->id) }}" target="_blank" class="btn btn-warning btn-sm font-weight-bold mr-2 mt-2 mt-md-0 text-dark">
                                <i class="fa fa-print mr-1"></i> Print Token
                            </a>
                            <a href="{{ route('service_jobs.index') }}" class="btn btn-light btn-sm font-weight-bold mt-2 mt-md-0">
                                <i class="fa fa-arrow-left mr-1"></i> Back to Tickets
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light border p-3 h-100">
                                <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Device & Warranty Details</h6>
                                <p class="mb-1"><strong>Product:</strong> {{ $serviceJob->product->name ?? 'N/A' }} ({{ $serviceJob->product->code ?? '' }})</p>
                                <p class="mb-1"><strong>Serial / IMEI:</strong> <code class="text-primary font-weight-bold">{{ $serviceJob->serial->serial_number ?? ($serviceJob->serial_number ?? 'N/A') }}</code></p>
                                <p class="mb-1"><strong>Current Status:</strong> <span class="badge badge-primary px-2 py-1 text-uppercase">{{ str_replace('_', ' ', $serviceJob->status) }}</span></p>
                                <p class="mb-0"><strong>Warehouse Branch:</strong> {{ $serviceJob->warehouse->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border p-3 h-100">
                                <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Customer & Intake Info</h6>
                                <p class="mb-1"><strong>Customer Name:</strong> {{ $serviceJob->customer_name }}</p>
                                <p class="mb-1"><strong>Phone Number:</strong> {{ $serviceJob->customer_phone }}</p>
                                <p class="mb-1"><strong>Intake Date:</strong> {{ \Carbon\Carbon::parse($serviceJob->created_at)->format('d M Y, h:i A') }}</p>
                                <p class="mb-0"><strong>Assigned Technician:</strong> {{ $serviceJob->technician->name ?? 'Unassigned' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border p-3 mb-4">
                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Fault Diagnostic & Service Description</h6>
                        <p class="mb-2"><strong>Reported Problem:</strong> {{ $serviceJob->problem_description }}</p>
                        @if($serviceJob->diagnosis_notes)
                            <p class="mb-0 text-info"><strong>Technician Diagnosis:</strong> {{ $serviceJob->diagnosis_notes }}</p>
                        @endif
                    </div>

                    <div class="card bg-light border p-3 mb-4">
                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">Service Costs & Billing</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Estimated Cost</small>
                                <h5 class="font-weight-bold text-dark mb-0">৳{{ number_format($serviceJob->estimated_cost ?? 0, 2) }}</h5>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Actual Service Charge</small>
                                <h5 class="font-weight-bold text-success mb-0">৳{{ number_format($serviceJob->service_charge ?? 0, 2) }}</h5>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Parts Cost</small>
                                <h5 class="font-weight-bold text-dark mb-0">৳{{ number_format($serviceJob->parts_cost ?? 0, 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
