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

@php
    $cls = ['pending' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', 'completed' => 'success', 'recorded' => 'info', 'cancelled' => 'secondary'][$transfer->status] ?? 'secondary';
    if ($transfer->refund_status === 'pending') { $cls = 'warning'; }
    $t = $transfer;
@endphp

<section class="forms">
    <div class="container-fluid">
        <a href="{{ route('money-transfers.index') }}" class="btn btn-sm btn-default mb-2"><i class="dripicons-arrow-thin-left"></i> All transfers</a>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ $t->reference_no }} <span class="badge badge-{{ $cls }} ml-2">{{ $t->status_label }}</span></h4>
                @if($can['cancel'])
                {!! Form::open(['route' => ['money-transfers.cancel', $t->id], 'method' => 'post', 'onsubmit' => "return confirm('Cancel this transfer? The amount goes back to the sending account.')"]) !!}
                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel transfer</button>
                {!! Form::close() !!}
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Type</strong><br>{{ $t->type_label }}</div>
                    <div class="col-md-3"><strong>From</strong><br>{{ $t->fromAccount->name ?? '-' }}@if($t->fromWarehouse)<br><small class="text-muted">{{ $t->fromWarehouse->name }}</small>@endif</div>
                    <div class="col-md-3"><strong>To</strong><br>
                        @if($t->toAccount){{ $t->toAccount->name }}@if($t->toWarehouse)<br><small class="text-muted">{{ $t->toWarehouse->name }}</small>@endif
                        @elseif($t->third_party_name){{ $t->third_party_name }}<br><small class="text-muted">{{ $t->third_party_details }}</small>
                        @else Expense @endif
                    </div>
                    <div class="col-md-3"><strong>Amount sent</strong><br><span style="font-size:1.3rem">{{ money($t->amount) }}</span></div>
                    <div class="col-md-3 mt-3"><strong>Created</strong><br>{{ $t->created_at->format('d/m/Y h:i A') }}@if($t->creator)<br><small class="text-muted">by {{ $t->creator->name }}</small>@endif</div>
                    <div class="col-md-3 mt-3"><strong>Carried by</strong><br>{{ $t->carried_by ?: '-' }}</div>
                    <div class="col-md-6 mt-3"><strong>Note</strong><br>{{ $t->note ?: '-' }}</div>
                </div>
            </div>
        </div>

        {{-- what happened --}}
        @if($t->responded_at || $t->status === 'cancelled' || $t->refund_status)
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">History</h5></div>
            <div class="card-body">
                @if($t->responded_at)
                <p>
                    <strong>{{ $t->status === 'rejected' ? 'Rejected' : 'Answered' }}</strong> on {{ $t->responded_at->format('d/m/Y h:i A') }}
                    by {{ $t->responder->name ?? '-' }}. <strong>Received by:</strong> {{ $t->received_by_name }}.
                    <strong>Accepted:</strong> {{ money($t->accepted_amount) }} of {{ money($t->amount) }}.
                </p>
                @if($t->response_note)<p><strong>Reason:</strong> {{ $t->response_note }}</p>@endif
                @endif
                @if($t->refund_status)
                <p>
                    <strong>Refund:</strong> {{ money($t->refund_amount) }} &mdash;
                    @if($t->refund_status === 'pending')<span class="badge badge-warning">waiting for the sending side to accept</span>
                    @else <span class="badge badge-success">accepted</span> on {{ optional($t->refund_completed_at)->format('d/m/Y h:i A') }} @endif
                </p>
                @endif
                @if($t->status === 'cancelled')<p>Cancelled on {{ optional($t->cancelled_at)->format('d/m/Y h:i A') }}. The amount went back to the sending account.</p>@endif
            </div>
        </div>
        @endif

        {{-- receiving side answers --}}
        @if($can['respond'])
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Accept or reject this transfer</h5></div>
            <div class="card-body">
                {!! Form::open(['route' => ['money-transfers.respond', $t->id], 'method' => 'post']) !!}
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Received by (name) *</label>
                        <input type="text" name="received_by_name" class="form-control" value="{{ old('received_by_name', auth()->user()->name) }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Carried by</label>
                        <input type="text" name="carried_by" class="form-control" value="{{ old('carried_by', $t->carried_by) }}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox d-inline-block mr-4"><input type="radio" name="decision" id="d-accept" value="accept" {{ old('decision', 'accept') === 'accept' ? 'checked' : '' }}><label for="d-accept">Accept full {{ money($t->amount) }}</label></div>
                    <div class="checkbox d-inline-block mr-4"><input type="radio" name="decision" id="d-partial" value="partial" {{ old('decision') === 'partial' ? 'checked' : '' }}><label for="d-partial">Accept only part</label></div>
                    <div class="checkbox d-inline-block"><input type="radio" name="decision" id="d-reject" value="reject" {{ old('decision') === 'reject' ? 'checked' : '' }}><label for="d-reject">Reject all</label></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group" id="partial-box" style="display:none">
                        <label>Amount accepted (৳)</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $t->amount }}" name="accepted_amount" class="form-control" value="{{ old('accepted_amount') }}">
                        <small class="text-muted">The rest goes back to the sender as a refund.</small>
                    </div>
                    <div class="col-md-8 form-group" id="reason-box" style="display:none">
                        <label>Reason * <small class="text-muted">(required when anything is rejected; it stays in the history)</small></label>
                        <input type="text" name="response_note" class="form-control" value="{{ old('response_note') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Confirm</button>
                {!! Form::close() !!}
            </div>
        </div>
        @endif

        {{-- sending side takes the money back --}}
        @if($can['refund'])
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Refund of {{ money($t->refund_amount) }} is waiting for you</h5></div>
            <div class="card-body">
                <p>Confirm when the returned money has reached you. It then goes back into {{ $t->fromAccount->name ?? 'the sending account' }}.</p>
                {!! Form::open(['route' => ['money-transfers.refund', $t->id], 'method' => 'post']) !!}
                <button type="submit" class="btn btn-success">Accept refund</button>
                {!! Form::close() !!}
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #money-transfer-menu").addClass("active");

    function syncDecision() {
        var d = $('input[name="decision"]:checked').val();
        $('#partial-box').toggle(d === 'partial');
        $('#reason-box').toggle(d === 'partial' || d === 'reject');
        $('[name="response_note"]').prop('required', d === 'partial' || d === 'reject');
        $('[name="accepted_amount"]').prop('required', d === 'partial');
    }
    $('input[name="decision"]').on('change', syncDecision);
    syncDecision();
</script>
@endpush
