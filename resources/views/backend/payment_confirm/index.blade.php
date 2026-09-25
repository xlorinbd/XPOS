@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <h4 class="mb-1">Gateway Payments</h4>
        <p class="text-muted">Card and gateway payments taken at the counter count in the account only after they are confirmed here.</p>
        <div class="mt-2">
            <a href="{{ route('payments.pending') }}" class="btn btn-sm {{ $tab === 'pending' ? 'btn-dark' : 'btn-outline-dark' }}">Waiting for confirmation</a>
            <a href="{{ route('payments.pending', ['tab' => 'confirmed']) }}" class="btn btn-sm {{ $tab === 'confirmed' ? 'btn-dark' : 'btn-outline-dark' }}">Confirmed</a>
            @if($tab === 'pending' && $pendingTotal > 0)
                <span class="ml-3 text-muted">Total waiting: <strong>{{ money($pendingTotal) }}</strong></span>
            @endif
        </div>
    </div>

    <div class="table-responsive mt-3">
        <table id="payment-confirm-table" class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sale</th>
                    <th>Branch</th>
                    <th>Account</th>
                    <th>Method</th>
                    <th class="text-right">Amount</th>
                    <th>{{ $tab === 'pending' ? 'Action' : 'Confirmed' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td data-order="{{ $r->created_at }}">{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y h:i A') }}</td>
                    <td>{{ $r->sale_reference }}</td>
                    <td>{{ $r->branch_name ?: '-' }}</td>
                    <td>{{ $r->account_name }}</td>
                    <td>{{ $r->paying_method }}</td>
                    <td class="text-right">{{ money($r->amount) }}</td>
                    <td>
                        @if($tab === 'pending')
                            <form method="POST" action="{{ route('payments.confirm', $r->id) }}" class="form-inline">
                                @csrf
                                <input type="text" name="confirm_note" class="form-control form-control-sm mr-2" placeholder="Settlement ref (optional)" maxlength="191">
                                <button type="submit" class="btn btn-sm btn-dark">Confirm</button>
                            </form>
                        @else
                            {{ $r->confirmed_by_name }} &middot; {{ $r->confirmed_at ? \Carbon\Carbon::parse($r->confirmed_at)->format('d/m/Y h:i A') : '' }}
                            @if($r->confirm_note)<br><small class="text-muted">{{ $r->confirm_note }}</small>@endif
                        @endif
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
    $("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #payment-confirm-menu").addClass("active");
    $('#payment-confirm-table').DataTable({ order: [[0, 'desc']], pageLength: 25 });
</script>
@endpush
