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

<section class="forms">
    <div class="container-fluid">
        <a href="{{ route('money-transfers.index') }}" class="btn btn-sm btn-default mb-2"><i class="dripicons-arrow-thin-left"></i> All transfers</a>
        <div class="card">
            <div class="card-header"><h4>New Cash Transfer</h4></div>
            <div class="card-body">
                {!! Form::open(['route' => 'money-transfers.store', 'method' => 'post', 'id' => 'transfer-form']) !!}
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Transfer type *</label>
                        <select name="type_code" id="type_code" class="form-control" required>
                            <option value="">Select type...</option>
                            @foreach($types as $code => $t)
                            <option value="{{ $code }}" {{ old('type_code') == $code ? 'selected' : '' }}>{{ $t['label'] }}{{ $t['approval'] ? '  (needs acceptance)' : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" id="type-help"></small>
                    </div>
                </div>

                <div id="transfer-fields" style="display:none">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>From account *</label>
                            <select name="from_account_id" id="from_account_id" class="form-control" required></select>
                            <small class="text-muted" id="from-balance"></small>
                        </div>
                        <div class="col-md-4 form-group target target-account">
                            <label>To account *</label>
                            <select name="to_account_id" id="to_account_id" class="form-control"></select>
                        </div>
                        <div class="col-md-4 form-group target target-expense">
                            <label>Expense category *</label>
                            <select name="expense_category_id" class="form-control">
                                <option value="">Select category...</option>
                                @foreach($expenseCategories as $c)<option value="{{ $c->id }}" {{ old('expense_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                            </select>
                            <small class="text-muted">An expense record is created automatically.</small>
                        </div>
                        <div class="col-md-4 form-group target target-third_party">
                            <label>Sent to (name) *</label>
                            <input type="text" name="third_party_name" class="form-control" value="{{ old('third_party_name') }}" placeholder="Person or organisation">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Amount (৳) *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group target target-third_party">
                            <label>Details of the recipient / reason</label>
                            <input type="text" name="third_party_details" class="form-control" value="{{ old('third_party_details') }}" placeholder="Phone, account, on whose instruction">
                        </div>
                        <div class="col-md-3 form-group target target-account">
                            <label>Carried by</label>
                            <input type="text" name="carried_by" class="form-control" value="{{ old('carried_by') }}" placeholder="Who takes the cash">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Note</label>
                            <input type="text" name="note" class="form-control" value="{{ old('note') }}">
                        </div>
                    </div>
                    <div class="alert alert-info" id="approval-note" style="display:none">
                        The amount leaves the sending account now and is held <strong>in transit</strong>. It reaches the receiving account only when the receiving side accepts it.
                    </div>
                    <button type="submit" class="btn btn-primary">Send transfer</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #money-transfer-menu").addClass("active");

    var TYPES = {!! json_encode($types) !!};
    var oldFrom = {!! json_encode(old('from_account_id')) !!}, oldTo = {!! json_encode(old('to_account_id')) !!};

    function fill(select, items, keep) {
        select.empty().append('<option value="">Select account...</option>');
        $.each(items, function (i, a) { select.append($('<option>').val(a.id).text(a.name).data('balance', a.balance)); });
        if (keep) { select.val(keep); }
    }

    function applyType() {
        var code = $('#type_code').val(), t = TYPES[code];
        $('#transfer-fields').toggle(!!t);
        if (!t) { return; }
        fill($('#from_account_id'), t.senders, oldFrom);
        fill($('#to_account_id'), t.receivers, oldTo);
        $('.target').hide(); $('.target-' + t.target).show();
        $('#to_account_id').prop('required', t.target === 'account');
        $('[name="third_party_name"]').prop('required', t.target === 'third_party');
        $('[name="expense_category_id"]').prop('required', t.target === 'expense');
        $('#approval-note').toggle(t.approval);
        $('#type-help').text(t.senders.length ? '' : 'You have no account that can send this kind of transfer.');
        showBalance();
    }
    function showBalance() {
        var opt = $('#from_account_id option:selected'), b = opt.data('balance');
        $('#from-balance').text(b === undefined ? '' : 'Available: ' + kgMoney(b));
    }
    $('#type_code').on('change', function () { oldFrom = null; oldTo = null; applyType(); });
    $('#from_account_id').on('change', showBalance);
    applyType();
</script>
@endpush
