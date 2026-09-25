@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <h4 class="mb-1">Cash Transfers</h4>
        <p class="text-muted">Branch and Warehouse transfers wait for the receiving side to accept. Everything else completes at once. Nothing is ever deleted; a wrong transfer is corrected by rejecting or cancelling it.</p>
        @can('money-transfer')
        <a href="{{ route('money-transfers.create') }}" class="btn btn-info"><i class="dripicons-plus"></i> New Transfer</a>
        @endcan
        <div class="mt-3">
            <a href="{{ route('money-transfers.index') }}" class="btn btn-sm {{ $tab === 'all' ? 'btn-dark' : 'btn-outline-dark' }}">All</a>
            <a href="{{ route('money-transfers.index', ['tab' => 'incoming']) }}" class="btn btn-sm {{ $tab === 'incoming' ? 'btn-dark' : 'btn-outline-dark' }}">
                Waiting for my acceptance @if($counts['incoming'])<span class="badge badge-warning">{{ $counts['incoming'] }}</span>@endif
            </a>
            <a href="{{ route('money-transfers.index', ['tab' => 'refunds']) }}" class="btn btn-sm {{ $tab === 'refunds' ? 'btn-dark' : 'btn-outline-dark' }}">
                Refunds to accept @if($counts['refunds'])<span class="badge badge-warning">{{ $counts['refunds'] }}</span>@endif
            </a>
            <a href="{{ route('money-transfers.index', ['tab' => 'sent']) }}" class="btn btn-sm {{ $tab === 'sent' ? 'btn-dark' : 'btn-outline-dark' }}">Sent</a>
        </div>
    </div>

    <div class="table-responsive mt-3">
        <table id="money-transfer-table" class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Accepted</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $t)
                @php
                    $cls = ['pending' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', 'completed' => 'success', 'recorded' => 'info', 'cancelled' => 'secondary'][$t->status] ?? 'secondary';
                    if ($t->refund_status === 'pending') { $cls = 'warning'; }
                @endphp
                <tr>
                    <td data-order="{{ $t->created_at }}">{{ $t->created_at->format('d/m/Y h:i A') }}</td>
                    <td><a href="{{ route('money-transfers.show', $t->id) }}"><strong>{{ $t->reference_no }}</strong></a></td>
                    <td>{{ $t->type_label }}</td>
                    <td>{{ $t->fromAccount->name ?? '-' }}</td>
                    <td>{{ $t->toAccount->name ?? ($t->third_party_name ?: ($t->type_code === 'staff_to_expense' ? 'Expense' : '-')) }}</td>
                    <td class="text-right">{{ money($t->amount) }}</td>
                    <td class="text-right">{{ $t->status === 'pending' ? '-' : money($t->accepted_amount) }}</td>
                    <td><span class="badge badge-{{ $cls }}">{{ $t->status_label }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #money-transfer-menu").addClass("active");
    $('#money-transfer-table').DataTable({ order: [[0, 'desc']], pageLength: 25 });
</script>
@endpush
