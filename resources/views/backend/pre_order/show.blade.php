@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm border-0" style="border-radius:12px;">
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-truck mr-2"></i> Pre-Order Details: #{{ $preOrder->order_reference }}</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Inter-branch pre-order booking specifications and tracking.</p>
                        </div>
                        <a href="{{ route('pre_orders.index') }}" class="btn btn-light btn-sm font-weight-bold mt-2 mt-md-0">
                            <i class="fa fa-arrow-left mr-1"></i> Back to Pre-Orders
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light border p-3 h-100">
                                <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Order Summary</h6>
                                <p class="mb-1"><strong>Status:</strong> <span class="badge badge-info px-2 py-1 text-uppercase">{{ $preOrder->status }}</span></p>
                                <p class="mb-1"><strong>Booked On:</strong> {{ \Carbon\Carbon::parse($preOrder->created_at)->format('d M Y, h:i A') }}</p>
                                <p class="mb-1"><strong>Expected Delivery:</strong> {{ $preOrder->expected_delivery_date ?? 'Not specified' }}</p>
                                <p class="mb-0"><strong>Created By:</strong> {{ $preOrder->user->name ?? 'Staff' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border p-3 h-100">
                                <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2">Customer Information</h6>
                                <p class="mb-1"><strong>Name:</strong> {{ $preOrder->customer_name }}</p>
                                <p class="mb-1"><strong>Phone:</strong> {{ $preOrder->customer_phone }}</p>
                                <p class="mb-0"><strong>Customer Record:</strong> {{ $preOrder->customer->name ?? 'Walk-in Customer' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border p-3 mb-4">
                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">Device & Logistics Routing</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Product:</strong> {{ $preOrder->product->name ?? 'N/A' }} ({{ $preOrder->product->code ?? '' }})</p>
                                <p class="mb-1"><strong>Reserved Serial:</strong> <code class="text-primary font-weight-bold">{{ $preOrder->serial_number ?: 'Any available unit' }}</code></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Source Branch (Stock):</strong> {{ $preOrder->fromWarehouse->name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Destination Branch (Pickup):</strong> {{ $preOrder->toWarehouse->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border p-3 mb-4">
                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">Financial Settlement</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Agreed Selling Price</small>
                                <h5 class="font-weight-bold text-dark mb-0">৳{{ number_format($preOrder->price, 2) }}</h5>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Advance Paid</small>
                                <h5 class="font-weight-bold text-success mb-0">৳{{ number_format($preOrder->advance_amount, 2) }}</h5>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Remaining Due on Delivery</small>
                                <h5 class="font-weight-bold text-danger mb-0">৳{{ number_format(max(0, $preOrder->price - $preOrder->advance_amount), 2) }}</h5>
                            </div>
                        </div>
                    </div>

                    @if($preOrder->notes)
                    <div class="alert alert-secondary mb-0">
                        <strong>Order Notes:</strong> {{ $preOrder->notes }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
