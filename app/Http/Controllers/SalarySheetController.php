<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Employee;
use App\Models\HrmSetting;
use App\Models\Payroll;
use App\Models\SalarySheet;
use App\Models\SalarySheetLine;
use App\Models\Warehouse;
use App\Services\SalaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Monthly salary sheet: generate from attendance, adjust (Eid bonus, other deduction), then finalize
 * to pay. Finalizing writes one payroll row per employee (which debits the chosen account) and records
 * the loan instalments that were taken out.
 */
class SalarySheetController extends Controller
{
    public function __construct(private SalaryService $salary)
    {
    }

    private function allowed(): bool
    {
        $role = Role::find(Auth::user()->role_id);
        return $role && $role->hasPermissionTo('payroll');
    }

    private function deny()
    {
        return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    private function employeesFor(?int $warehouseId)
    {
        $q = Employee::where('is_active', true)->orderBy('name');
        if ($warehouseId) {
            $q->whereIn('user_id', function ($sub) use ($warehouseId) {
                $sub->select('users.id')->from('users')
                    ->where(function ($w) use ($warehouseId) {
                        $w->where('users.warehouse_id', $warehouseId)
                          ->orWhereIn('users.id', DB::table('user_warehouses')->where('warehouse_id', $warehouseId)->pluck('user_id'));
                    });
            });
        }
        return $q->get();
    }

    private function validMonth(?string $month): string
    {
        return preg_match('/^\d{4}-\d{2}$/', (string) $month) ? $month : date('Y-m');
    }

    public function index(Request $request)
    {
        if (!$this->allowed()) {
            return $this->deny();
        }
        $month = $this->validMonth($request->input('month'));
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $sheet = SalarySheet::where('month', $month)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId), fn($q) => $q->whereNull('warehouse_id'))
            ->first();
        $lines = $sheet ? $sheet->lines()->with('employee')->get()->sortBy(fn($l) => $l->employee->name ?? '')->values() : collect();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $accounts = Account::where('is_active', true)->where('type', '!=', 'Staff Wallet')->orderBy('name')->get();
        $settings = HrmSetting::current();

        return view('backend.salary_sheet.index', compact('sheet', 'lines', 'month', 'warehouseId', 'warehouses', 'accounts', 'settings'));
    }

    public function generate(Request $request)
    {
        if (!$this->allowed()) {
            return $this->deny();
        }
        $month = $this->validMonth($request->input('month'));
        $warehouseId = (int) $request->input('warehouse_id', 0) ?: null;

        $sheet = SalarySheet::firstOrCreate(['month' => $month, 'warehouse_id' => $warehouseId], ['status' => 'draft', 'created_by' => Auth::id()]);
        if ($sheet->status === 'final') {
            return redirect()->back()->with('not_permitted', 'This month is already paid and closed.');
        }

        $employees = $this->employeesFor($warehouseId);
        if ($employees->isEmpty()) {
            return redirect()->back()->with('not_permitted', 'No active staff found for this selection. Add staff under HRM > Employee first.');
        }

        $settings = HrmSetting::current();
        DB::transaction(function () use ($sheet, $employees, $month, $settings) {
            $old = $sheet->lines()->get()->keyBy('employee_id');
            $sheet->lines()->delete();
            foreach ($employees as $e) {
                $calc = $this->salary->calculate($e, $month, $settings);
                $prev = $old->get($e->id);
                $calc['eid_bonus'] = $prev ? (float) $prev->eid_bonus : 0.0;
                $calc['other_deduction'] = $prev ? (float) $prev->other_deduction : 0.0;
                $calc['net_pay'] = $this->salary->net($calc);
                $calc['details'] = json_encode($calc['details']);
                SalarySheetLine::create($calc + [
                    'salary_sheet_id' => $sheet->id,
                    'employee_id' => $e->id,
                    'note' => $prev->note ?? null,
                ]);
            }
        });

        return redirect()->route('salary.index', ['month' => $month, 'warehouse_id' => $warehouseId ?: 0])->with('message', 'Salary sheet calculated from the attendance entered so far.');
    }

    /** Save the manual columns (Eid bonus, other deduction, note) and refresh net pay. */
    public function update(Request $request, $id)
    {
        if (!$this->allowed()) {
            return $this->deny();
        }
        $sheet = SalarySheet::findOrFail($id);
        if ($sheet->status === 'final') {
            return redirect()->back()->with('not_permitted', 'A paid month cannot be changed.');
        }

        foreach ((array) $request->input('lines', []) as $lineId => $vals) {
            $line = SalarySheetLine::where('salary_sheet_id', $sheet->id)->find($lineId);
            if (!$line) {
                continue;
            }
            $line->eid_bonus = max(0, (float) ($vals['eid_bonus'] ?? 0));
            $line->other_deduction = max(0, (float) ($vals['other_deduction'] ?? 0));
            $line->note = mb_substr((string) ($vals['note'] ?? ''), 0, 255) ?: null;
            $line->net_pay = $this->salary->net($line->toArray());
            $line->save();
        }
        return redirect()->back()->with('message', 'Salary sheet saved.');
    }

    public function finalize(Request $request, $id)
    {
        if (!$this->allowed()) {
            return $this->deny();
        }
        $request->validate(['account_id' => 'required|integer|exists:accounts,id']);
        $sheet = SalarySheet::findOrFail($id);
        if ($sheet->status === 'final') {
            return redirect()->back()->with('not_permitted', 'This month is already paid.');
        }
        $account = Account::findOrFail($request->account_id);

        DB::transaction(function () use ($sheet, $account) {
            foreach ($sheet->lines()->with('employee')->get() as $line) {
                $payroll = null;
                if ($line->net_pay > 0) {
                    $payroll = Payroll::create([
                        'reference_no' => 'payroll-' . date('Ymd') . '-' . $line->employee_id . '-' . $line->id,
                        'employee_id' => $line->employee_id,
                        'account_id' => $account->id,
                        'user_id' => Auth::id(),
                        'amount' => $line->net_pay,
                        'paying_method' => $account->type,
                        'note' => 'Salary ' . $sheet->month,
                        'status' => 'paid',
                        'amount_array' => json_encode([
                            'salary' => (float) $line->basic_salary, 'absent' => (float) $line->absent_deduction,
                            'minutes' => (float) $line->minute_deduction, 'eid_bonus' => (float) $line->eid_bonus,
                            'loan' => (float) $line->loan_deduction, 'other' => (float) $line->other_deduction, 'total' => (float) $line->net_pay,
                        ]),
                        'month' => $sheet->month,
                    ]);
                    $line->payroll_id = $payroll->id;
                    $line->save();
                }
                $this->recordLoanInstalments($line, $sheet);
            }
            $sheet->update(['status' => 'final', 'account_id' => $account->id, 'finalized_at' => now()]);
        });

        return redirect()->back()->with('message', 'Salary paid and the month is closed.');
    }

    /** Take the instalments out of the running loans by the amount that was deducted on the line. */
    private function recordLoanInstalments(SalarySheetLine $line, SalarySheet $sheet): void
    {
        $left = (float) $line->loan_deduction;
        if ($left <= 0) {
            return;
        }
        $loans = \App\Models\StaffLoan::where('employee_id', $line->employee_id)->where('status', 'approved')->where('remaining', '>', 0)
            ->where(fn($q) => $q->whereNull('start_month')->orWhere('start_month', '<=', $sheet->month))->orderBy('id')->get();
        foreach ($loans as $loan) {
            if ($left <= 0) {
                break;
            }
            if (DB::table('staff_loan_repayments')->where('staff_loan_id', $loan->id)->where('month', $sheet->month)->exists()) {
                continue;
            }
            $take = min($left, (float) $loan->installment_amount, (float) $loan->remaining);
            DB::table('staff_loan_repayments')->insert([
                'staff_loan_id' => $loan->id, 'month' => $sheet->month, 'amount' => $take, 'salary_sheet_id' => $sheet->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $loan->remaining = round((float) $loan->remaining - $take, 2);
            if ($loan->remaining <= 0.009) {
                $loan->remaining = 0;
                $loan->status = 'repaid';
            }
            $loan->save();
            $left -= $take;
        }
    }

    public function saveSettings(Request $request)
    {
        if (!$this->allowed()) {
            return $this->deny();
        }
        $days = array_values(array_intersect((array) $request->input('weekly_off', []), SalaryService::DAY_KEYS));
        $settings = HrmSetting::current();
        $settings->update([
            'weekly_off' => implode(',', $days),
            'checkin' => $request->input('duty_start') ? $request->input('duty_start') . ':00' : $settings->checkin,
            'checkout' => $request->input('duty_end') ? $request->input('duty_end') . ':00' : $settings->checkout,
        ]);
        return redirect()->back()->with('message', 'HR settings saved. Regenerate the salary sheet to use them.');
    }
}
