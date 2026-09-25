<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySheetLine extends Model
{
    protected $fillable = [
        'salary_sheet_id', 'employee_id', 'basic_salary', 'working_days', 'absent_days', 'absent_deduction',
        'late_minutes', 'early_minutes', 'per_minute_rate', 'minute_deduction', 'eid_bonus', 'loan_deduction',
        'other_deduction', 'net_pay', 'details', 'note', 'payroll_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDetailsArrayAttribute(): array
    {
        return $this->details ? (json_decode($this->details, true) ?: []) : [];
    }
}
