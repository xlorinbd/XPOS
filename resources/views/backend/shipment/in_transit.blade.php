@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <h4 class="mb-1">In-Transit Products</h4>
        <p class="text-muted">Foreign purchases that are still on the way. These are <strong>not</strong> in sellable stock and cannot be sold or pre-ordered until the final entry is done in the Bangladesh warehouse.</p>
        <a href="{{ route('shipments.index') }}" class="btn btn-default">Shipments</a>
    </div>

    <div class="table-responsive mt-3">
        <table id="transit-table" class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Model</th>
                    <th class="text-right">Coming (not in stock yet)</th>
                    <th class="text-right">On a shipment</th>
                    <th class="text-right">Not shipped yet</th>
                    <th>Purchases</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byProduct as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['model'] }}</td>
                    <td class="text-right"><strong>{{ amount_format($row['open']) }}</strong></td>
                    <td class="text-right">{{ amount_format($row['shipped']) }}</td>
                    <td class="text-right">{{ amount_format($row['open'] - $row['shipped']) }}</td>
                    <td>{{ implode(', ', $row['purchases']) }}</td>
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
    $("ul#purchase #in-transit-menu").addClass("active");
    $('#transit-table').DataTable({ order: [], pageLength: 25, language: { emptyTable: 'No products are in transit.' } });
</script>
@endpush
