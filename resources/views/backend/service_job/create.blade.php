@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>Receive Device for Service / RMA</h4>
                        <a href="{{ route('service_jobs.index') }}" class="btn btn-info btn-sm">
                            <i class="dripicons-list"></i> All Tickets
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>

                        <!-- Serial Search Form -->
                        <form method="GET" action="{{ route('service_jobs.create') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <label><strong>Scan / Enter Serial Number or IMEI *</strong></label>
                                    <div class="input-group">
                                        <input type="text" name="serial_number" class="form-control" placeholder="Type or scan serial..." value="{{ request('serial_number') }}" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="dripicons-search"></i> Look Up Device
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        @if(request('serial_number') && !$serial)
                            <div class="alert alert-danger">
                                No record found for serial: "{{ request('serial_number') }}". Make sure it is registered in the system.
                            </div>
                        @elseif($serial)
                            @if($serial->status !== 'sold')
                                <div class="alert alert-warning">
                                    This device cannot be received for service because its current status is <strong>{{ strtoupper($serial->status) }}</strong> (Must be 'SOLD').
                                </div>
                            @else
                                <form id="intakeForm" onsubmit="return false;">
                                    @csrf
                                    <input type="hidden" name="serial_number" value="{{ $serial->serial_number }}">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr><th colspan="2">Device Information</th></tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td style="width:40%;"><strong>Product:</strong></td>
                                                        <td>{{ $product->name }} ({{ $product->code }})</td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Serial No:</strong></td>
                                                        <td><code>{{ $serial->serial_number }}</code></td>
                                                    </tr>
                                                    @if($product->processor || $product->ram || $product->storage || $product->display)
                                                    <tr>
                                                        <td><strong>Specs:</strong></td>
                                                        <td>{{ implode(' | ', array_filter([$product->processor, $product->ram, $product->storage, $product->display])) }}</td>
                                                    </tr>
                                                    @endif
                                                    @if($sale)
                                                    <tr>
                                                        <td><strong>Sold Date:</strong></td>
                                                        <td>{{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y') }} (Ref: {{ $sale->reference_no }})</td>
                                                    </tr>
                                                    @endif
                                                    <tr>
                                                        <td><strong>Warranty:</strong></td>
                                                        <td>
                                                            @if($isWarrantyActive)
                                                                <span class="badge badge-success">Active ({{ $warrantyDaysRemaining }} days remaining)</span>
                                                            @else
                                                                <span class="badge badge-danger">Expired / None</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Customer Name *</label>
                                                <input type="text" name="customer_name" class="form-control" value="{{ $sale && $sale->customer ? $sale->customer->name : '' }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Customer Phone *</label>
                                                <input type="text" name="customer_phone" class="form-control" value="{{ $sale && $sale->customer ? $sale->customer->phone_number : '' }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Receiving Branch *</label>
                                                <select name="warehouse_id" class="form-control selectpicker" required>
                                                    @foreach($warehouses as $wh)
                                                        <option value="{{ $wh->id }}" {{ Auth::user()->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <h5><strong>Diagnosis & Condition</strong></h5>
                                            <hr>
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label>Customer-Reported Problem / Defect Description *</label>
                                            <textarea name="problem_description" class="form-control" rows="3" required placeholder="Describe the fault (e.g. Battery not charging, display flickering, keyboard keys unresponsive)..."></textarea>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Physical Condition & Body Scratches</label>
                                            <input type="text" name="condition_notes" class="form-control" placeholder="e.g. Minor scratches on bottom panel...">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Accessories Received with Device</label>
                                            <input type="text" name="accessories_received" class="form-control" placeholder="e.g. Original Charger, Box...">
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label class="d-flex align-items-center">
                                                <input type="checkbox" id="is_warranty_covered" name="is_warranty_covered" value="1" {{ $isWarrantyActive ? 'checked' : '' }}>&nbsp;&nbsp;
                                                <strong>Covered Under Free Service Warranty (Parts charges may apply if physical damage)</strong>
                                            </label>
                                        </div>
                                    </div>

                                    <div id="intakeAlert" class="mb-3 d-none"></div>

                                    <div class="form-group mt-3">
                                        <button type="submit" id="btnSubmitIntake" class="btn btn-primary">
                                            <i class="dripicons-print"></i> Receive & Print Token
                                        </button>
                                        <a href="{{ route('service_jobs.index') }}" class="btn btn-secondary">
                                            {{trans('file.Cancel')}}
                                        </a>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $('#intakeForm').on('submit', function(e) {
        e.preventDefault();
        $('#btnSubmitIntake').prop('disabled', true).text('Receiving...');
        $('#intakeAlert').addClass('d-none');

        $.ajax({
            url: '{{ route("service_jobs.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                $('#btnSubmitIntake').prop('disabled', false).text('Receive & Print Token');
                if (resp && resp.success) {
                    $('#intakeAlert').removeClass('d-none alert-danger').addClass('alert alert-success')
                        .text(resp.message + ' Opening service token for print...');
                    setTimeout(function() {
                        window.open(resp.print_url, '_blank');
                        window.location.href = '{{ route("service_jobs.index") }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                $('#btnSubmitIntake').prop('disabled', false).text('Receive & Print Token');
                var msg = 'Failed to submit service intake.';
                if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                $('#intakeAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(msg);
            }
        });
    });
</script>
@endpush
