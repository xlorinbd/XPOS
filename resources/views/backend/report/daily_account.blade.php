@extends('backend.layout.main')
@section('content')
<x-error-message key="not_permitted" />
<style>
    .kg-da h3 { font-size: 15px; margin: 22px 0 8px; border-bottom: 2px solid #111; padding-bottom: 4px; }
    .kg-da table.da { width: 100%; }
    .kg-da table.da th { background: #111; color: #fff; padding: 6px 8px; font-size: 12px; }
    .kg-da table.da td { border-bottom: 1px solid #e0e0e0; padding: 6px 8px; vertical-align: top; }
    .kg-da tr.tot td { font-weight: 700; border-top: 2px solid #111; }
    .kg-da .r { text-align: right; }
    .kg-da .muted { color: #777; font-size: 12px; }
    .kg-da table.da-head { width: 100%; }
</style>
<section>
    <div class="container-fluid">
        <h4 class="mb-1">Daily Account</h4>
        <p class="text-muted">Opening balance, sales, expenses, transfers and the closing cash of one branch for one day. Customer details are never shown here.</p>
        <form method="GET" action="{{ route('report.daily_account') }}" class="form-inline mb-3">
            <label class="mr-2">Branch</label>
            <select name="warehouse_id" class="form-control mr-3">
                @if($isGlobal)<option value="0" @selected($warehouseId === 0)>Head office (company accounts)</option>@endif
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected($warehouseId === (int) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
            <label class="mr-2">Date</label>
            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="form-control mr-3">
            <button type="submit" class="btn btn-dark mr-2">Show</button>
            <button type="submit" name="pdf" value="1" class="btn btn-outline-dark">Download PDF</button>
        </form>
    </div>
    <div class="container-fluid" style="max-width:1000px">
        @include('backend.report._daily_account_body', ['r' => $report])
    </div>
</section>
@endsection
@push('scripts')
<script>
    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
</script>
@endpush
