@php
    $kgUser = Auth::user();
    $kgIsGlobal = (int) $kgUser->role_id <= 2;
    $kgPicked = $kgIsGlobal ? (int) request('branch', 0) : 0;
    $kg = app(\App\Services\KgDashboardService::class)->overview($kgUser, $kgPicked ?: null);
    $kgBranchList = \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get();
    $card = function ($label, $value, $href = null, $hint = null, $tone = null) {
        $tones = ['danger' => 'border-danger text-danger', 'warning' => 'border-warning', null => ''];
        return compact('label', 'value', 'href', 'hint', 'tone');
    };
    $kgCards = [
        $card('Sales this month', money($kg['month']['sales']), url('sales'), $kg['month']['invoices'] . ' invoice(s)'),
        $card('Purchase this month', money($kg['month']['purchase']), url('purchases')),
        $card('Expense this month', money($kg['month']['expense']), url('expenses')),
        $card('Products', number_format($kg['products']), url('products')),
        $card('Stock in hand', rtrim(rtrim(number_format($kg['stock'], 2), '0'), '.'), url('report/warehouse_stock'), 'units'),
        $card('Low stock', $kg['low_stock'], url('report/product_quantity_alert'), 'below their own alert level', $kg['low_stock'] > 0 ? 'danger' : null),
        $card('Pending warranty', $kg['pending_warranty'], url('service_jobs'), 'jobs not finished', $kg['pending_warranty'] > 0 ? 'warning' : null),
        $card('Pending purchase', $kg['pending_purchase'], url('purchases'), $kg['shipments_in_transit'] . ' shipment(s) in transit', $kg['pending_purchase'] > 0 ? 'warning' : null),
        $card('Pending transfer', $kg['pending_transfer'], url('transfers'), 'leaving these branches', $kg['pending_transfer'] > 0 ? 'warning' : null),
        $card('Incoming transfer', $kg['pending_incoming'], url('transfers'), 'on the way in', $kg['pending_incoming'] > 0 ? 'warning' : null),
        $card('Cash to accept', $kg['cash_incoming'], route('money-transfers.index'), 'cash transfers / refunds waiting', $kg['cash_incoming'] > 0 ? 'warning' : null),
        $card('Gateway payments', $kg['pending_gateway'], route('payments.pending'), 'waiting for confirmation', $kg['pending_gateway'] > 0 ? 'warning' : null),
    ];
    $kgToday = [
        $card("Today's sales", money($kg['today']['sales']), url('sales'), $kg['today']['invoices'] . ' invoice(s)'),
        $card("Today's purchase", money($kg['today']['purchase']), url('purchases')),
        $card("Today's expense", money($kg['today']['expense']), url('expenses')),
    ];
    if (isset($kg['today']['profit'])) {
        $kgToday[] = $card("Today's profit", money($kg['today']['profit']), null, 'after cost, expense and damage');
    }
@endphp
<style>
    #alertSection { display: none !important; } /* the original package's upgrade notice is not for the shop */
    .kg-dash .kg-card { display:block; background:#fff; border:1px solid #e2e2e2; border-radius:6px; padding:12px 14px; height:100%; color:#111; }
    .kg-dash a.kg-card:hover { border-color:#111; text-decoration:none; }
    .kg-dash .kg-card .v { font-size:1.35rem; font-weight:700; line-height:1.2; }
    .kg-dash .kg-card .l { font-size:.8rem; color:#555; text-transform:uppercase; letter-spacing:.03em; }
    .kg-dash .kg-card .h { font-size:.75rem; color:#777; }
    .kg-dash .kg-card.danger { border-left:4px solid #c62828; }
    .kg-dash .kg-card.warning { border-left:4px solid #f0b400; }
    .kg-dash h6 { margin:18px 0 8px; font-weight:700; }
    .kg-dash table th { font-size:.75rem; text-transform:uppercase; color:#555; }
</style>
<section class="kg-dash pt-0">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <h6 class="m-0">@if($kgIsGlobal){{ $kgPicked ? optional($kgBranchList->firstWhere('id', $kgPicked))->name : 'All branches' }}@else Your branch @endif</h6>
            @if($kgIsGlobal)
            <form method="GET" class="form-inline">
                <select name="branch" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="0">All branches</option>
                    @foreach($kgBranchList as $b)<option value="{{ $b->id }}" @selected($kgPicked === (int) $b->id)>{{ $b->name }}</option>@endforeach
                </select>
            </form>
            @endif
        </div>

        <h6>Today</h6>
        <div class="row">
            @foreach($kgToday as $c)
            <div class="col-6 col-md-3 mb-3">
                @if($c['href'])<a href="{{ $c['href'] }}" class="kg-card">@else<div class="kg-card">@endif
                    <div class="l">{{ $c['label'] }}</div><div class="v">{{ $c['value'] }}</div>@if($c['hint'])<div class="h">{{ $c['hint'] }}</div>@endif
                @if($c['href'])</a>@else</div>@endif
            </div>
            @endforeach
        </div>

        <h6>Overview</h6>
        <div class="row">
            @foreach($kgCards as $c)
            <div class="col-6 col-md-3 col-lg-2 mb-3">
                <a href="{{ $c['href'] }}" class="kg-card {{ $c['tone'] }}">
                    <div class="l">{{ $c['label'] }}</div><div class="v">{{ $c['value'] }}</div>@if($c['hint'])<div class="h">{{ $c['hint'] }}</div>@endif
                </a>
            </div>
            @endforeach
        </div>

        <div class="row">
            @if(count($kg['branches']) > 1)
            <div class="col-lg-7 mb-3">
                <h6>Branch-wise sales &amp; stock (this month)</h6>
                <div class="table-responsive"><table class="table table-sm">
                    <thead><tr><th>Branch</th><th class="text-right">Invoices</th><th class="text-right">Sales</th><th class="text-right">Stock</th></tr></thead>
                    <tbody>
                    @foreach($kg['branches'] as $b)
                        <tr><td>{{ $b['name'] }}</td><td class="text-right">{{ $b['invoices'] }}</td><td class="text-right">{{ money($b['sales']) }}</td><td class="text-right">{{ rtrim(rtrim(number_format($b['stock'], 2), '0'), '.') }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            </div>
            @endif
            <div class="col-lg-5 mb-3">
                <h6>Cash summary</h6>
                <div class="table-responsive"><table class="table table-sm">
                    <tbody>
                    @forelse($kg['cash']['rows'] as $r)
                        <tr><td>{{ $r['name'] }}</td><td class="text-right">{{ money($r['balance']) }}</td></tr>
                    @empty
                        <tr><td class="text-muted">No account balances yet.</td></tr>
                    @endforelse
                        <tr class="font-weight-bold"><td>Total</td><td class="text-right">{{ money($kg['cash']['total']) }}</td></tr>
                    </tbody>
                </table></div>
                <a href="{{ url('accounts') }}" class="small">Open accounts</a>
                @can('account-statement') &middot; <a href="{{ route('report.daily_account') }}" class="small">Daily account</a> @endcan
            </div>
        </div>
    </div>
</section>
