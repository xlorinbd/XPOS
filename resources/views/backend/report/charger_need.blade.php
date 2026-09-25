@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <h4 class="mb-1">Charger Need List</h4>
        <p class="text-muted">
            Laptops in stock are matched only with chargers of the same model. Shortage = laptops &minus; chargers.
            Link a laptop to its charger model in the product form (Charger / Adapter model); mark charger products with "This product is a charger".
        </p>
    </div>

    <div class="table-responsive">
        <table class="table" id="charger-total-table">
            <thead>
                <tr>
                    <th>Charger model</th>
                    <th>Code</th>
                    <th class="text-right">Laptops in stock</th>
                    <th class="text-right">Chargers in stock</th>
                    <th class="text-right">Shortage (need)</th>
                    <th class="text-right">Surplus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($total as $modelId => $t)
                    @php
                        $diff = $t['laptops'] - $t['chargers'];
                        $model = $models[$modelId] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $model->name ?? ('Model #'.$modelId) }}</td>
                        <td>{{ $model->code ?? '' }}</td>
                        <td class="text-right">{{ amount_format($t['laptops']) }}</td>
                        <td class="text-right">{{ amount_format($t['chargers']) }}</td>
                        <td class="text-right">@if($diff > 0)<span class="badge badge-danger">{{ amount_format($diff) }}</span>@else 0 @endif</td>
                        <td class="text-right">@if($diff < 0)<span class="badge badge-info">{{ amount_format(-$diff) }}</span>@else 0 @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No products are linked to a charger model yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="container-fluid mt-4">
        <h5>Branch-wise</h5>
    </div>
    <div class="table-responsive">
        <table class="table" id="charger-branch-table">
            <thead>
                <tr>
                    <th>Charger model</th>
                    <th>Branch / Warehouse</th>
                    <th class="text-right">Laptops</th>
                    <th class="text-right">Chargers</th>
                    <th class="text-right">Shortage</th>
                    <th class="text-right">Surplus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($branchWise as $modelId => $perBranch)
                    @foreach($perBranch as $whId => $t)
                        @php $diff = $t['laptops'] - $t['chargers']; @endphp
                        <tr>
                            <td>{{ $models[$modelId]->name ?? ('Model #'.$modelId) }}</td>
                            <td>{{ $warehouses[$whId]->name ?? ('#'.$whId) }}</td>
                            <td class="text-right">{{ amount_format($t['laptops']) }}</td>
                            <td class="text-right">{{ amount_format($t['chargers']) }}</td>
                            <td class="text-right">@if($diff > 0)<span class="badge badge-danger">{{ amount_format($diff) }}</span>@else 0 @endif</td>
                            <td class="text-right">@if($diff < 0)<span class="badge badge-info">{{ amount_format(-$diff) }}</span>@else 0 @endif</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #charger-need-menu").addClass("active");
    $('#charger-branch-table').DataTable({ order: [], pageLength: 25 });
</script>
@endpush
