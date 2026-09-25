@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <h4 class="mb-1">Salary Sheet</h4>
        <p class="text-muted">Fixed monthly salary. A working day without attendance is Absent (that day's pay is cut). Late arrival and early leave are cut minute by minute:
            per minute = monthly salary &divide; working days &divide; duty hours &divide; 60. Running loan and advance instalments are taken out automatically.</p>

        <form method="GET" action="{{ route('salary.index') }}" class="form-inline mb-3">
            <label class="mr-2">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control mr-3">
            <label class="mr-2">Branch</label>
            <select name="warehouse_id" class="form-control mr-3">
                <option value="0" @selected($warehouseId === 0)>All staff</option>
                @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected($warehouseId === (int) $w->id)>{{ $w->name }}</option>@endforeach
            </select>
            <button class="btn btn-outline-dark mr-2" type="submit">Open</button>
        </form>

        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="POST" action="{{ route('salary.settings') }}" class="form-inline">
                    @csrf
                    <strong class="mr-3">Weekly off</strong>
                    @foreach(['sat' => 'Sat', 'sun' => 'Sun', 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri'] as $k => $label)
                        <label class="mr-3 mb-0"><input type="checkbox" name="weekly_off[]" value="{{ $k }}" @checked(in_array($k, $settings->weeklyOffDays(), true))> {{ $label }}</label>
                    @endforeach
                    <span class="mx-2 text-muted">|</span>
                    <label class="mr-2 mb-0">Default duty</label>
                    <input type="time" name="duty_start" class="form-control form-control-sm mr-1" value="{{ substr($settings->checkin ?? '10:00:00', 0, 5) }}">
                    <span class="mr-1">to</span>
                    <input type="time" name="duty_end" class="form-control form-control-sm mr-3" value="{{ substr($settings->checkout ?? '19:00:00', 0, 5) }}">
                    <button class="btn btn-sm btn-dark" type="submit">Save</button>
                    <small class="text-muted ml-3">A staff member's own shift (HRM &gt; Shift) overrides the default duty time. Holidays come from HRM &gt; Holiday.</small>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('salary.generate') }}" class="mb-3">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
            @if(!$sheet || $sheet->status === 'draft')
                <button type="submit" class="btn btn-dark">{{ $sheet ? 'Recalculate from attendance' : 'Calculate salary sheet' }}</button>
                <small class="text-muted ml-2">Bonus and extra deductions you typed are kept when you recalculate.</small>
            @else
                <span class="badge badge-success">Paid {{ $sheet->finalized_at?->format('d/m/Y') }}</span>
            @endif
        </form>
    </div>

    @if($sheet)
    <form method="POST" action="{{ route('salary.update', $sheet->id) }}" id="sheet-form">
        @csrf
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th class="text-right">Salary</th>
                        <th class="text-center">Working days</th>
                        <th class="text-right">Absent</th>
                        <th class="text-right">Late / Early (min)</th>
                        <th class="text-right">Minute cut</th>
                        <th class="text-right">Loan</th>
                        <th style="width:110px">Eid bonus</th>
                        <th style="width:110px">Other cut</th>
                        <th class="text-right">Net pay</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @php $tot = ['basic' => 0, 'net' => 0]; @endphp
                @foreach($lines as $l)
                    @php $tot['basic'] += $l->basic_salary; $tot['net'] += $l->net_pay; $det = $l->details_array; @endphp
                    <tr>
                        <td><strong>{{ $l->employee->name ?? '-' }}</strong><br><small class="text-muted">{{ $det['duty'] ?? '' }}</small></td>
                        <td class="text-right">{{ money($l->basic_salary) }}</td>
                        <td class="text-center">{{ $l->working_days }}</td>
                        <td class="text-right">{{ $l->absent_days }} day{{ $l->absent_days == 1 ? '' : 's' }}<br><small class="text-danger">{{ $l->absent_deduction > 0 ? '- ' . money($l->absent_deduction) : '' }}</small></td>
                        <td class="text-right">{{ $l->late_minutes }} / {{ $l->early_minutes }}<br><small class="text-muted">{{ rtrim(rtrim(number_format($l->per_minute_rate, 4), '0'), '.') }} / min</small></td>
                        <td class="text-right">{{ $l->minute_deduction > 0 ? '- ' . money($l->minute_deduction) : '-' }}</td>
                        <td class="text-right">{{ $l->loan_deduction > 0 ? '- ' . money($l->loan_deduction) : '-' }}</td>
                        <td><input type="number" step="any" min="0" name="lines[{{ $l->id }}][eid_bonus]" value="{{ $l->eid_bonus + 0 }}" class="form-control form-control-sm" @disabled($sheet->status === 'final')></td>
                        <td>
                            <input type="number" step="any" min="0" name="lines[{{ $l->id }}][other_deduction]" value="{{ $l->other_deduction + 0 }}" class="form-control form-control-sm" @disabled($sheet->status === 'final')>
                            <input type="hidden" name="lines[{{ $l->id }}][note]" value="{{ $l->note }}">
                        </td>
                        <td class="text-right"><strong>{{ money($l->net_pay) }}</strong></td>
                        <td><button type="button" class="btn btn-sm btn-link kg-detail" data-title="{{ $l->employee->name ?? '' }}" data-detail='@json($det)'>Details</button></td>
                    </tr>
                @endforeach
                    <tr class="font-weight-bold">
                        <td>Total</td><td class="text-right">{{ money($tot['basic']) }}</td><td colspan="7"></td><td class="text-right">{{ money($tot['net']) }}</td><td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @if($sheet->status === 'draft')
        <div class="container-fluid mb-4">
            <button type="submit" class="btn btn-outline-dark">Save changes</button>
        </div>
        @endif
    </form>

    @if($sheet->status === 'draft' && $lines->count())
    <div class="container-fluid mb-5">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('salary.finalize', $sheet->id) }}" class="form-inline" onsubmit="return confirm('Pay the salaries and close this month? This cannot be undone.');">
                    @csrf
                    <label class="mr-2"><strong>Pay from account</strong></label>
                    <select name="account_id" class="form-control mr-3" required>
                        @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                    </select>
                    <button type="submit" class="btn btn-dark">Pay salaries &amp; close month</button>
                    <small class="text-muted ml-3">Save your bonus / deduction changes first.</small>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endif
</section>

<div class="modal fade" id="kgDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="kgDetailTitle"></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body" id="kgDetailBody" style="font-size:13px"></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
    $(document).on('click', '.kg-detail', function () {
        var d = $(this).data('detail') || {};
        var h = '<p>Duty time: <strong>' + (d.duty || '-') + '</strong> &middot; one day of pay: <strong>' + kgMoney(d.per_day || 0) + '</strong></p>';
        var list = function (t, arr, fn) { return arr && arr.length ? '<p><strong>' + t + '</strong><br>' + arr.map(fn).join('<br>') + '</p>' : ''; };
        h += list('Absent days', d.absent_dates, function (x) { return x; });
        h += list('Late arrival', d.late, function (x) { return x.date + ': in ' + x.in + ', ' + x.minutes + ' min'; });
        h += list('Early leave', d.early, function (x) { return x.date + ': out ' + x.out + ', ' + x.minutes + ' min'; });
        h += list('Approved leave (not cut)', d.leave_dates, function (x) { return x; });
        h += list('Out time missing (early leave not counted)', d.no_out_time, function (x) { return x; });
        if (h.indexOf('<strong>Absent') === -1 && h.indexOf('Late') === -1 && h.indexOf('Early') === -1) h += '<p class="text-success mb-0">Nothing was cut for attendance.</p>';
        $('#kgDetailTitle').text($(this).data('title'));
        $('#kgDetailBody').html(h);
        $('#kgDetailModal').modal('show');
    });
</script>
@endpush
