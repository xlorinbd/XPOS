@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<section>
    <div class="container-fluid">
        <h4 class="mb-1">Shipments</h4>
        <p class="text-muted">Foreign purchases travel as shipments: In-Transit, then received in the Bangladesh warehouse, then a final entry adds them to stock.</p>
        <a href="{{ route('shipments.create') }}" class="btn btn-info"><i class="dripicons-plus"></i> New Shipment</a>
        <a href="{{ route('shipments.inTransit') }}" class="btn btn-default">In-Transit Products</a>
        <div class="mt-3">
            <a href="{{ route('shipments.index') }}" class="btn btn-sm {{ !$status ? 'btn-dark' : 'btn-outline-dark' }}">All</a>
            @foreach(\App\Models\Shipment::STATUSES as $key => $label)
                <a href="{{ route('shipments.index', ['status' => $key]) }}" class="btn btn-sm {{ $status === $key ? 'btn-dark' : 'btn-outline-dark' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
            @endforeach
        </div>
    </div>

    <div class="table-responsive mt-3">
        <table id="shipment-table" class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Carrier</th>
                    <th>Shipment date</th>
                    <th>Expected</th>
                    <th>Arrived</th>
                    <th>Destination</th>
                    <th>Items</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipments as $s)
                <tr>
                    <td><a href="{{ route('shipments.show', $s->id) }}"><strong>{{ $s->reference_no }}</strong></a></td>
                    <td>{{ $s->type_label }}</td>
                    <td>{{ $s->carrier_name ?: '-' }}</td>
                    <td data-order="{{ optional($s->shipment_date)->format('Y-m-d') }}">{{ optional($s->shipment_date)->format('d/m/Y') ?: '-' }}</td>
                    <td data-order="{{ optional($s->expected_arrival)->format('Y-m-d') }}">{{ optional($s->expected_arrival)->format('d/m/Y') ?: '-' }}</td>
                    <td data-order="{{ optional($s->actual_arrival)->format('Y-m-d') }}">{{ optional($s->actual_arrival)->format('d/m/Y') ?: '-' }}</td>
                    <td>{{ $s->warehouse->name ?? '-' }}</td>
                    <td>{{ $s->items_count }}</td>
                    <td>
                        @php $cls = ['in_transit' => 'warning', 'received' => 'info', 'final_entry' => 'success', 'cancelled' => 'secondary'][$s->status] ?? 'secondary'; @endphp
                        <span class="badge badge-{{ $cls }}">{{ $s->status_label }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#purchase").siblings('a').attr('aria-expanded','true');
    $("ul#purchase").addClass("show");
    $("ul#purchase #shipment-list-menu").addClass("active");
    $('#shipment-table').DataTable({ order: [[0, 'desc']], pageLength: 25 });
</script>
@endpush
