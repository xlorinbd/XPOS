<?php

namespace Tests\Feature\KhanGadget;

use App\Models\Employee;
use App\Models\HrmSetting;
use App\Services\SalaryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryTest extends KgTestCase
{
    private function employee(float $salary): Employee
    {
        return Employee::forceCreate([
            'name' => 'T Staff', 'email' => 't@example.com', 'phone_number' => '0', 'department_id' => 0, 'designation_id' => 0,
            'basic_salary' => $salary, 'is_active' => 1,
        ]);
    }

    public function test_absent_late_and_early_are_cut_exactly(): void
    {
        $settings = HrmSetting::current();
        $settings->update(['weekly_off' => 'fri', 'checkin' => '10:00:00', 'checkout' => '19:00:00']);
        $e = $this->employee(30000);

        // August 2026: 31 days, 4 Fridays -> 27 working days
        for ($d = Carbon::parse('2026-08-01'); $d->lte(Carbon::parse('2026-08-31')); $d->addDay()) {
            if ($d->dayOfWeek === 5) {
                continue;
            }
            $date = $d->format('Y-m-d');
            if ($date === '2026-08-03') {
                continue; // absent
            }
            $in = $date === '2026-08-04' ? '10:30:00' : '10:00:00';   // 30 minutes late
            $out = $date === '2026-08-05' ? '18:00:00' : '19:00:00';  // 60 minutes early
            DB::table('attendances')->insert(['date' => $date, 'employee_id' => $e->id, 'user_id' => 1, 'checkin' => $in, 'checkout' => $out, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $line = app(SalaryService::class)->calculate($e, '2026-08', $settings);

        $this->assertSame(27, $line['working_days']);
        $this->assertSame(1, $line['absent_days']);
        $this->assertEquals(1111.11, $line['absent_deduction']);
        $this->assertSame(30, $line['late_minutes']);
        $this->assertSame(60, $line['early_minutes']);
        // per minute = 30000 / 27 / 9 hours / 60
        $this->assertEqualsWithDelta(2.0576, $line['per_minute_rate'], 0.0001);
        $this->assertEquals(185.19, $line['minute_deduction']);
        $this->assertEquals(28703.70, app(SalaryService::class)->net($line));
    }

    public function test_approved_leave_and_holidays_are_not_cut(): void
    {
        $settings = HrmSetting::current();
        $settings->update(['weekly_off' => 'fri', 'checkin' => '10:00:00', 'checkout' => '19:00:00']);
        $e = $this->employee(27000);

        DB::table('holidays')->insert(['user_id' => 1, 'from_date' => '2026-08-10', 'to_date' => '2026-08-11', 'is_approved' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('leaves')->insert(['employee_id' => $e->id, 'leave_types' => 1, 'start_date' => '2026-08-12', 'end_date' => '2026-08-12', 'days' => 1, 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now()]);

        // present every other working day
        for ($d = Carbon::parse('2026-08-01'); $d->lte(Carbon::parse('2026-08-31')); $d->addDay()) {
            if ($d->dayOfWeek === 5 || in_array($d->format('Y-m-d'), ['2026-08-10', '2026-08-11', '2026-08-12'], true)) {
                continue;
            }
            DB::table('attendances')->insert(['date' => $d->format('Y-m-d'), 'employee_id' => $e->id, 'user_id' => 1, 'checkin' => '10:00:00', 'checkout' => '19:00:00', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $line = app(SalaryService::class)->calculate($e, '2026-08', $settings);
        $this->assertSame(25, $line['working_days'], '27 working days minus 2 holidays');
        $this->assertSame(0, $line['absent_days'], 'the leave day is not an absence');
        $this->assertEquals(27000, app(SalaryService::class)->net($line));
    }
}
