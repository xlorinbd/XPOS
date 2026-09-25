@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid" style="max-width:900px">
        <h4 class="mb-1">My Loan / Advance Request</h4>
        @if(!$employee)
            <div class="alert alert-warning">Your login is not linked to a staff record, so a request cannot be made. Ask the admin to link it under HRM &gt; Employee.</div>
        @else
        <p class="text-muted">You apply for yourself. Before the request is saved, a 6 digit code is sent to your saved e-mail (<strong>{{ $maskedEmail ?: 'none saved' }}</strong>). Management then checks and approves it by hand.</p>

        <form method="POST" action="{{ route('loan.apply') }}" class="card card-body mb-4" id="loan-form">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Type</label>
                    <select name="kind" id="loan-kind" class="form-control">
                        <option value="loan" @selected(old('kind') === 'loan')>Loan (paid back in instalments)</option>
                        <option value="advance" @selected(old('kind') === 'advance')>Advance (taken out of next salary)</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" min="1" step="any" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-4 form-group" id="loan-inst">
                    <label>Instalments (months)</label>
                    <input type="number" name="installments" min="1" max="36" class="form-control" value="{{ old('installments', 3) }}">
                </div>
                <div class="col-md-12 form-group">
                    <label>Reason *</label>
                    <input type="text" name="reason" maxlength="255" class="form-control" value="{{ old('reason') }}" required>
                </div>
            </div>
            <div class="row align-items-end">
                <div class="col-md-4 form-group">
                    <button type="button" class="btn btn-outline-dark btn-block" id="send-otp">Send code to my e-mail</button>
                </div>
                <div class="col-md-4 form-group">
                    <label>Code from e-mail *</label>
                    <input type="text" name="otp" inputmode="numeric" maxlength="6" class="form-control" placeholder="6 digits" required>
                </div>
                <div class="col-md-4 form-group">
                    <button type="submit" class="btn btn-dark btn-block">Submit request</button>
                </div>
            </div>
            <div id="otp-msg" class="small"></div>
        </form>

        <h5>My requests</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Date</th><th>Type</th><th class="text-right">Amount</th><th class="text-right">Still to pay</th><th>Status</th><th>Note</th></tr></thead>
                <tbody>
                @forelse($loans as $l)
                    <tr>
                        <td>{{ $l->created_at->format('d/m/Y') }}</td>
                        <td>{{ ucfirst($l->kind) }}</td>
                        <td class="text-right">{{ money($l->amount) }}</td>
                        <td class="text-right">{{ $l->status === 'approved' || $l->status === 'repaid' ? money($l->remaining) : '-' }}</td>
                        <td>@php $cls = ['pending' => 'warning', 'approved' => 'success', 'repaid' => 'secondary', 'rejected' => 'danger'][$l->status] ?? 'secondary'; @endphp<span class="badge badge-{{ $cls }}">{{ ucfirst($l->status) }}</span></td>
                        <td>{{ $l->decision_note }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center">No requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
    $('#loan-kind').on('change', function () { $('#loan-inst').toggle($(this).val() === 'loan'); }).trigger('change');
    $('#send-otp').on('click', function () {
        var $b = $(this).prop('disabled', true);
        $.post('{{ route('loan.otp') }}', { _token: '{{ csrf_token() }}' })
            .done(function (r) { $('#otp-msg').attr('class', 'small text-success').text(r.message); setTimeout(function () { $b.prop('disabled', false); }, 30000); })
            .fail(function (x) { $('#otp-msg').attr('class', 'small text-danger').text((x.responseJSON && x.responseJSON.error) || 'Could not send the code.'); $b.prop('disabled', false); });
    });
</script>
@endpush
