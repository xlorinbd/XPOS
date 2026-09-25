<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLoan extends Model
{
    protected $fillable = [
        'employee_id', 'kind', 'amount', 'installments', 'installment_amount', 'start_month', 'remaining', 'reason', 'status',
        'otp_verified_at', 'requested_by', 'approved_by', 'approved_at', 'pay_account_id', 'expense_id', 'decision_note',
    ];

    protected $casts = ['otp_verified_at' => 'datetime', 'approved_at' => 'datetime'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function repayments()
    {
        return $this->hasMany(StaffLoanRepayment::class);
    }
}
