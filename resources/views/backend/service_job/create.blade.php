@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-wrench mr-2"></i> Receive Device for Service / RMA</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Log a new service intake, generate an RMA tracking token, and capture fault details.</p>
                        </div>
                        <a href="{{ route('service_jobs.index') }}" class="btn btn-light btn-sm font-weight-bold">
                            <i class="fa fa-list mr-1"></i> View All Tickets
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Search or Input Serial Form -->
                    <form method="GET" action="{{ route('service_jobs.create') }}" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="serial_number" class="form-control form-control-lg" placeholder="Scan or Enter Serial Number / IMEI..." value="{{ request('serial_number') }}" required>
                            <div class="input-group-append">
                                <button class="btn btn-danger font-weight-bold px-4" type="submit">
                                    <i class="fa fa-search mr-1"></i> Look Up Device
                                </button>
                            </div>
                        </div>
                    </form>

                    @if(request('serial_number') && !$serial)
                        <div class="alert alert-danger font-weight-bold">
                            <i class="fa fa-exclamation-triangle mr-2"></i> No record found for serial: "{{ request('serial_number') }}". Make sure it is registered and sold.
                        </div>
                    @elseif($serial)
                        @if($serial->status !== 'sold')
                            <div class="alert alert-warning font-weight-bold">
                                <i class="fa fa-exclamation-circle mr-2"></i> This device cannot be received for service because its current status is <u>{{ strtoupper($serial->status) }}</u> (Must be 'SOLD').
                            </div>
                        @else
                            <form id="intakeForm" onsubmit="return false;">
                                @csrf
                                <input type="hidden" name="serial_number" value="{{ $serial->serial_number }}">

                                <div class="row">
                                    <!-- Device & Warranty Info Card -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light border p-3 h-100">
                                            <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Device Information</h6>
                                            <p class="mb-1"><strong>Product:</strong> {{ $product->name }} ({{ $product->code }})</p>
                                            <p class="mb-1"><strong>Serial No:</strong> <code class="text-primary">{{ $serial->serial_number }}</code></p>
                                            @if($product->processor || $product->ram || $product->storage || $product->display)
                                            <p class="mb-1"><strong>Specs:</strong> {{ implode(' | ', array_filter([$product->processor, $product->ram, $product->storage, $product->display])) }}</p>
                                            @endif
                                            @if($sale)
                                                <p class="mb-1"><strong>Sold Date:</strong> {{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y') }} (Ref: {{ $sale->reference_no }})</p>
                                            @endif
                                            <p class="mb-0">
                                                <strong>Warranty Status:</strong>
                                                @if($isWarrantyActive)
                                                    <span class="badge badge-success px-2 py-1"><i class="fa fa-check mr-1"></i> Active ({{ $warrantyDaysRemaining }} days remaining)</span>
                                                @else
                                                    <span class="badge badge-danger px-2 py-1"><i class="fa fa-times mr-1"></i> Expired / None</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Customer & Branch Info -->
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light border p-3 h-100">
                                            <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Customer & Branch</h6>
                                            <div class="form-group mb-2">
                                                <label class="font-weight-bold small mb-0">Customer Name *</label>
                                                <input type="text" name="customer_name" class="form-control form-control-sm" value="{{ $sale && $sale->customer ? $sale->customer->name : '' }}" required>
                                            </div>
                                            <div class="form-group mb-2">
                                                <label class="font-weight-bold small mb-0">Customer Phone *</label>
                                                <input type="text" name="customer_phone" class="form-control form-control-sm" value="{{ $sale && $sale->customer ? $sale->customer->phone_number : '' }}" required>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label class="font-weight-bold small mb-0">Receiving Branch *</label>
                                                <select name="warehouse_id" class="form-control form-control-sm" required>
                                                    @foreach($warehouses as $wh)
                                                        <option value="{{ $wh->id }}" {{ Auth::user()->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fault & Condition Section -->
                                <div class="card bg-light border p-3 mb-3">
                                    <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Intake Diagnosis & Condition</h6>
                                    <div class="form-group">
                                        <label class="font-weight-bold small">Customer-Reported Problem / Defect Description *</label>
                                        <textarea name="problem_description" class="form-control" rows="3" required placeholder="Describe the fault (e.g. Battery not charging, display flickering, keyboard keys unresponsive)..."></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label class="font-weight-bold small">Physical Condition & Body Scratches</label>
                                            <input type="text" name="condition_notes" class="form-control" placeholder="e.g. Dent on top lid, minor scratches on bottom panel...">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="font-weight-bold small">Accessories Received with Device</label>
                                            <input type="text" name="accessories_received" class="form-control" placeholder="e.g. Original 65W Type-C Charger, Sleeve Bag...">
                                        </div>
                                    </div>

                                    <div class="form-group mb-0">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_warranty_covered" name="is_warranty_covered" value="1" {{ $isWarrantyActive ? 'checked' : '' }}>
                                            <label class="custom-control-label font-weight-bold text-dark" for="is_warranty_covered">
                                                Covered Under Free Service Warranty (Parts charges may apply if physical damage)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div id="intakeAlert" class="mb-3 d-none"></div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" id="btnSubmitIntake" class="btn btn-danger btn-lg font-weight-bold px-5">
                                        <i class="fa fa-print mr-2"></i> Receive & Print Service Token
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    $('#intakeForm').on('submit', function(e) {
        e.preventDefault();
        $('#btnSubmitIntake').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-2"></span> Receiving...');
        $('#intakeAlert').addClass('d-none');

        $.ajax({
            url: '{{ route("service_jobs.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                $('#btnSubmitIntake').prop('disabled', false).html('<i class="fa fa-print mr-2"></i> Receive & Print Service Token');
                if (resp && resp.success) {
                    $('#intakeAlert').removeClass('d-none alert-danger').addClass('alert alert-success')
                        .html('<strong>' + resp.message + '</strong><br>Opening service token for print...');
                    setTimeout(function() {
                        window.open(resp.print_url, '_blank');
                        window.location.href = '{{ route("service_jobs.index") }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                $('#btnSubmitIntake').prop('disabled', false).html('<i class="fa fa-print mr-2"></i> Receive & Print Service Token');
                var msg = 'Failed to submit service intake.';
                if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                $('#intakeAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
            }
        });
    });
</script>
@endpush
