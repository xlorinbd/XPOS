@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <h4 class="mb-1">Staff Loans &amp; Advances</h4>
        <p class="text-muted">Check each request, then approve and choose the account the money is paid from. The instalments are taken out of the monthly salary automatically.</p>
        <div class="mb-3">
            <a href="{{ route('loan.manage') }}" class="btn btn-sm {{ $tab === 'pending' ? 'btn-dark' : 'btn-outline-dark' }}">Waiting for approval</a>
            <a href="{{ route('loan.manage', ['tab' => 'running']) }}" class="btn btn-sm {{ $tab === 'running' ? 'btn-dark' : 'btn-outline-dark' }}">Running</a>
            <a href="{{ route('loan.manage', ['tab' => 'all']) }}" class="btn btn-sm {{ $tab === 'all' ? 'btn-dark' : 'btn-outline-dark' }}">All</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Date</th><th>Staff</th><th>Type</th><th class="text-right">Amount</th><th>Reason</th><th>Status</th><th style="min-width:360px">Action</th></tr></thead>
            <tbody>
            @forelse($loans as $l)
                <tr>
                    <td>{{ $l->created_at->format('d/m/Y') }}</td>
                    <td><strong>{{ $l->employee->name ?? '-' }}</strong>@if($l->otp_verified_at)<br><small class="text-success">e-mail code verified</small>@endif</td>
                    <td>{{ ucfirst($l->kind) }}@if($l->kind === 'loan')<br><small class="text-muted">{{ $l->installments }} instalment(s) asked</small>@endif</td>
                    <td class="text-right">{{ money($l->amount) }}@if($l->status === 'approved')<br><small class="text-muted">left {{ money($l->remaining) }}</small>@endif</td>
                    <td>{{ $l->reason }}</td>
                    <td>@php $cls = ['pending' => 'warning', 'approved' => 'success', 'repaid' => 'secondary', 'rejected' => 'danger'][$l->status] ?? 'secondary'; @endphp<span class="badge badge-{{ $cls }}">{{ ucfirst($l->status) }}</span>@if($l->decision_note)<br><small>{{ $l->decision_note }}</small>@endif</td>
                    <td>
                        @if($l->status === 'pending')
                        <form method="POST" action="{{ route('loan.approve', $l->id) }}" class="form-inline mb-1">
                            @csrf
                            <select name="pay_account_id" class="form-control form-control-sm mr-1" required style="max-width:190px">
                                @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} ({{ money($balances[$a->id] ?? 0) }})</option>@endforeach
                            </select>
                            @if($l->kind === 'loan')
                            <input type="number" name="installments" min="1" max="36" value="{{ $l->installments }}" class="form-control form-control-sm mr-1" style="width:70px" title="Instalments">
                            @else
                            <input type="hidden" name="installments" value="1">
                            @endif
                            <input type="month" name="start_month" value="{{ date('Y-m', strtotime('first day of next month')) }}" class="form-control form-control-sm mr-1" title="First salary month">
                            <button class="btn btn-sm btn-dark" type="submit" onclick="return confirm('Approve and pay this amount now?')">Approve &amp; pay</button>
                        </form>
                        <form method="POST" action="{{ route('loan.reject', $l->id) }}" class="form-inline">
                            @csrf
                            <input type="text" name="decision_note" class="form-control form-control-sm mr-1" placeholder="Reason for rejecting" required maxlength="255">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
</script>
@endpush
