<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HrmSetting;
use App\Models\StaffLoan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monthly salary, the way the client asked for it:
 *  - fixed monthly salary, no commission;
 *  - a working day without attendance is Absent: that day's full pay is cut;
 *  - late arrival and early leave are cut minute by minute, no minimum, no flat rule;
 *  - per-minute pay = monthly salary / working days of the month / daily duty hours / 60;
 *  - working days = days of the month - weekly off days - holidays (holiday list is kept in HR);
 *  - approved leave is not cut;
 *  - a running loan / advance instalment is taken out of the salary automatically.
 */
class SalaryService
{
    public const DAY_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    /**
     * Calculate one employee's line for a month (YYYY-MM). Nothing is saved here.
     */
    public function calculate(Employee $employee, string $month, ?HrmSetting $settings = null): array
    {
        $settings = $settings ?: HrmSetting::current();
        $first = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $last = $first->copy()->endOfMonth()->startOfDay();
        $today = Carbon::today();
        $weeklyOff = $settings->weeklyOffDays();

        $holidayDates = $this->holidayDates($first, $last);
        $leaveDates = $this->leaveDates($employee->id, $first, $last);

        [$dutyStart, $dutyEnd] = $this->dutyTimes($employee, $settings);
        $dutyMinutes = max(1, $dutyStart->diffInMinutes($dutyEnd));

        // working days of the month
        $workingDates = [];
        for ($d = $first->copy(); $d->lte($last); $d->addDay()) {
            $key = $d->format('Y-m-d');
            if (in_array(self::DAY_KEYS[$d->dayOfWeek], $weeklyOff, true) || isset($holidayDates[$key])) {
                continue;
            }
            $workingDates[] = $key;
        }
        $workingDays = count($workingDates);

        $basic = round((float) $employee->basic_salary, 2);
        $perDay = $workingDays > 0 ? $basic / $workingDays : 0.0;
        $perMinute = $workingDays > 0 ? $basic / $workingDays / ($dutyMinutes / 60) / 60 : 0.0;

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$first->format('Y-m-d'), $last->format('Y-m-d')])
            ->get()->groupBy(fn($a) => Carbon::parse($a->date)->format('Y-m-d'));

        $absentDates = [];
        $lateDays = [];
        $earlyDays = [];
        $noOutDates = [];
        $lateMinutes = 0;
        $earlyMinutes = 0;
        $minuteDeduction = 0.0;

        foreach ($workingDates as $key) {
            $day = Carbon::parse($key);
            if ($day->gt($today)) {
                continue; // days that have not happened yet are not judged
            }
            if (isset($leaveDates[$key])) {
                continue; // approved leave
            }
            $rows = $attendance->get($key);
            if (!$rows || $rows->isEmpty()) {
                $absentDates[] = $key;
                continue;
            }

            $in = $rows->pluck('checkin')->filter()->sort()->first();
            $out = $rows->pluck('checkout')->filter()->sort()->last();
            $late = 0;
            $early = 0;
            if ($in) {
                $inTime = $this->time($in);
                $late = $inTime->gt($dutyStart) ? $dutyStart->diffInMinutes($inTime) : 0;
            }
            if ($out) {
                $outTime = $this->time($out);
                $early = $outTime->lt($dutyEnd) ? $outTime->diffInMinutes($dutyEnd) : 0;
            } else {
                $noOutDates[] = $key;
            }
            if ($late > 0) {
                $lateDays[] = ['date' => $key, 'minutes' => $late, 'in' => substr((string) $in, 0, 5)];
            }
            if ($early > 0) {
                $earlyDays[] = ['date' => $key, 'minutes' => $early, 'out' => substr((string) $out, 0, 5)];
            }
            $lateMinutes += $late;
            $earlyMinutes += $early;
            // a single day can never cost more than that day's pay
            $minuteDeduction += min($perDay, ($late + $early) * $perMinute);
        }

        $absentDeduction = round(count($absentDates) * $perDay, 2);
        $minuteDeduction = round($minuteDeduction, 2);
        $loanDeduction = $this->loanDue($employee->id, $month);

        return [
            'basic_salary' => $basic,
            'working_days' => $workingDays,
            'absent_days' => count($absentDates),
            'absent_deduction' => $absentDeduction,
            'late_minutes' => $lateMinutes,
            'early_minutes' => $earlyMinutes,
            'per_minute_rate' => round($perMinute, 4),
            'minute_deduction' => $minuteDeduction,
            'eid_bonus' => 0.0,
            'loan_deduction' => $loanDeduction,
            'other_deduction' => 0.0,
            'details' => [
                'duty' => $dutyStart->format('h:i A') . ' - ' . $dutyEnd->format('h:i A'),
                'per_day' => round($perDay, 2),
                'absent_dates' => $absentDates,
                'late' => $lateDays,
                'early' => $earlyDays,
                'no_out_time' => $noOutDates,
                'leave_dates' => array_keys($leaveDates),
            ],
        ];
    }

    public function net(array $line): float
    {
        return round(
            (float) $line['basic_salary'] - (float) $line['absent_deduction'] - (float) $line['minute_deduction']
            - (float) $line['loan_deduction'] - (float) $line['other_deduction'] + (float) $line['eid_bonus'],
            2
        );
    }

    /** The instalment that falls due in this salary month for all running loans of the employee. */
    public function loanDue(int $employeeId, string $month): float
    {
        $due = 0.0;
        $loans = StaffLoan::where('employee_id', $employeeId)->where('status', 'approved')->where('remaining', '>', 0)
            ->where(fn($q) => $q->whereNull('start_month')->orWhere('start_month', '<=', $month))->get();
        foreach ($loans as $loan) {
            $already = DB::table('staff_loan_repayments')->where('staff_loan_id', $loan->id)->where('month', $month)->exists();
            if ($already) {
                continue;
            }
            $due += min((float) $loan->installment_amount, (float) $loan->remaining);
        }
        return round($due, 2);
    }

    /** @return array<string, true> Y-m-d => true for every holiday date inside the month */
    private function holidayDates(Carbon $first, Carbon $last): array
    {
        $dates = [];
        $rows = Holiday::where(fn($q) => $q->whereNull('is_approved')->orWhere('is_approved', '!=', 0))->get();
        foreach ($rows as $h) {
            $from = Carbon::parse($h->from_date)->startOfDay();
            $to = Carbon::parse($h->to_date ?: $h->from_date)->startOfDay();
            if ($h->recurring) {
                $from = $from->copy()->year($first->year);
                $to = $to->copy()->year($first->year);
            }
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                if ($d->between($first, $last)) {
                    $dates[$d->format('Y-m-d')] = true;
                }
            }
        }
        return $dates;
    }

    private function leaveDates(int $employeeId, Carbon $first, Carbon $last): array
    {
        $dates = [];
        $rows = DB::table('leaves')->where('employee_id', $employeeId)->whereRaw('LOWER(status) = ?', ['approved'])
            ->where('start_date', '<=', $last->format('Y-m-d'))->where('end_date', '>=', $first->format('Y-m-d'))->get();
        foreach ($rows as $l) {
            for ($d = Carbon::parse($l->start_date)->startOfDay(); $d->lte(Carbon::parse($l->end_date)); $d->addDay()) {
                if ($d->between($first, $last)) {
                    $dates[$d->format('Y-m-d')] = true;
                }
            }
        }
        return $dates;
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function dutyTimes(Employee $employee, HrmSetting $settings): array
    {
        $shift = $employee->shift_id ? DB::table('shifts')->where('id', $employee->shift_id)->first() : null;
        $start = $shift->start_time ?? $settings->checkin ?? '10:00:00';
        $end = $shift->end_time ?? $settings->checkout ?? '19:00:00';
        return [$this->time($start), $this->time($end)];
    }

    private function time(string $value): Carbon
    {
        $value = trim($value);
        // 12 hour text such as "10:05 AM" or plain 24 hour "10:05:00"
        return Carbon::parse('2000-01-01 ' . $value);
    }
}
