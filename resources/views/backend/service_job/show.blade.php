@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>Service Job Ticket: {{ $serviceJob->job_number }}</h4>
                        <div>
                            <a href="{{ route('service_jobs.printToken', $serviceJob->id) }}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="dripicons-print"></i> Print Token
                            </a>
                            <a href="{{ route('service_jobs.index') }}" class="btn btn-default btn-sm">
                                <i class="dripicons-arrow-left"></i> Back to Tickets
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Device & Warranty Details</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Product:</strong></td>
                                            <td>{{ $serviceJob->product->name ?? 'N/A' }} ({{ $serviceJob->product->code ?? '' }})</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Serial / IMEI:</strong></td>
                                            <td><code>{{ $serviceJob->serial->serial_number ?? ($serviceJob->serial_number ?? 'N/A') }}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Current Status:</strong></td>
                                            <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $serviceJob->status)) }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Warehouse Branch:</strong></td>
                                            <td>{{ $serviceJob->warehouse->name ?? 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Customer & Intake Information</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Customer Name:</strong></td>
                                            <td>{{ $serviceJob->customer_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone Number:</strong></td>
                                            <td>{{ $serviceJob->customer_phone }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Intake Date:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($serviceJob->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Assigned Technician:</strong></td>
                                            <td>{{ $serviceJob->technician->name ?? 'Unassigned' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th>Fault Diagnosis & Service Description</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <p><strong>Reported Problem:</strong> {{ $serviceJob->problem_description }}</p>
                                                @if($serviceJob->condition_notes)
                                                    <p><strong>Physical Condition:</strong> {{ $serviceJob->condition_notes }}</p>
                                                @endif
                                                @if($serviceJob->accessories_received)
                                                    <p><strong>Accessories Received:</strong> {{ $serviceJob->accessories_received }}</p>
                                                @endif
                                                @if($serviceJob->diagnosis_notes)
                                                    <p class="text-info mb-0"><strong>Technician Diagnosis:</strong> {{ $serviceJob->diagnosis_notes }}</p>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="3">Service Costs & Billing</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <small class="text-muted d-block">Estimated Cost</small>
                                                <h5>{{ number_format($serviceJob->estimated_cost ?? 0, 2) }}</h5>
                                            </td>
                                            <td>
                                                <small class="text-muted d-block">Actual Service Charge</small>
                                                <h5 class="text-success">{{ number_format($serviceJob->service_charge ?? 0, 2) }}</h5>
                                            </td>
                                            <td>
                                                <small class="text-muted d-block">Parts Cost</small>
                                                <h5>{{ number_format($serviceJob->parts_cost ?? 0, 2) }}</h5>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
