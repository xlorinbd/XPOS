<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLoanRepayment extends Model
{
    protected $fillable = ['staff_loan_id', 'month', 'amount', 'salary_sheet_id'];
}
