@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>Pre-Order Details: #{{ $preOrder->order_reference }}</h4>
                        <a href="{{ route('pre_orders.index') }}" class="btn btn-default btn-sm">
                            <i class="dripicons-arrow-left"></i> Back to Pre-Orders
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Order Summary</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Status:</strong></td>
                                            <td><span class="badge badge-info">{{ ucfirst($preOrder->status) }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Booked On:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($preOrder->created_at)->format('d M Y, h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Expected Delivery:</strong></td>
                                            <td>{{ $preOrder->expected_delivery_date ?? 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created By:</strong></td>
                                            <td>{{ $preOrder->user->name ?? 'Staff' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Customer Information</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Name:</strong></td>
                                            <td>{{ $preOrder->customer_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td>{{ $preOrder->customer_phone }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer Record:</strong></td>
                                            <td>{{ $preOrder->customer->name ?? 'Walk-in Customer' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Device & Logistics Routing</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Product:</strong></td>
                                            <td>{{ $preOrder->product->name ?? 'N/A' }} ({{ $preOrder->product->code ?? '' }})</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reserved Serial:</strong></td>
                                            <td><code>{{ $preOrder->serial_number ?: 'Any available unit' }}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Source Branch (Stock):</strong></td>
                                            <td>{{ $preOrder->fromWarehouse->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Destination Branch:</strong></td>
                                            <td>{{ $preOrder->toWarehouse->name ?? 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="2">Financial Settlement</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:40%;"><strong>Agreed Price:</strong></td>
                                            <td><strong>{{ number_format($preOrder->price, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Advance Paid:</strong></td>
                                            <td><span class="text-success">{{ number_format($preOrder->advance_amount, 2) }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Remaining Due:</strong></td>
                                            <td><span class="text-danger font-weight-bold">{{ number_format(max(0, $preOrder->price - $preOrder->advance_amount), 2) }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if($preOrder->notes)
                        <div class="alert alert-secondary mt-3 mb-0">
                            <strong>Order Notes:</strong> {{ $preOrder->notes }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
