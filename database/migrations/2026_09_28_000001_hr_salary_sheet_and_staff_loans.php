<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // weekly holidays and the OTP channel live next to the other HR settings
        if (!Schema::hasColumn('hrm_settings', 'weekly_off')) {
            Schema::table('hrm_settings', function (Blueprint $t) {
                $t->string('weekly_off', 40)->default('fri'); // comma separated: sat,sun,mon,tue,wed,thu,fri
                $t->string('otp_channel', 10)->default('email'); // email | sms
            });
        }

        if (!Schema::hasTable('staff_loans')) {
            Schema::create('staff_loans', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('employee_id')->index();
                $t->string('kind', 10)->default('loan'); // loan | advance
                $t->decimal('amount', 12, 2);
                $t->unsignedSmallInteger('installments')->default(1);
                $t->decimal('installment_amount', 12, 2)->default(0);
                $t->string('start_month', 7)->nullable(); // first salary month the installment is deducted (YYYY-MM)
                $t->decimal('remaining', 12, 2)->default(0);
                $t->string('reason', 255)->nullable();
                $t->string('status', 12)->default('pending'); // pending | approved | rejected | repaid | cancelled
                $t->timestamp('otp_verified_at')->nullable();
                $t->unsignedBigInteger('requested_by')->nullable(); // the user who applied (must be the employee)
                $t->unsignedBigInteger('approved_by')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->unsignedBigInteger('pay_account_id')->nullable(); // the branch cash / account the money is paid from
                $t->unsignedBigInteger('expense_id')->nullable(); // the payout, booked as an expense of that account
                $t->string('decision_note', 255)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('staff_loan_repayments')) {
            Schema::create('staff_loan_repayments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('staff_loan_id')->index();
                $t->string('month', 7);
                $t->decimal('amount', 12, 2);
                $t->unsignedBigInteger('salary_sheet_id')->nullable();
                $t->timestamps();
                $t->unique(['staff_loan_id', 'month']);
            });
        }

        if (!Schema::hasTable('salary_sheets')) {
            Schema::create('salary_sheets', function (Blueprint $t) {
                $t->id();
                $t->string('month', 7); // YYYY-MM
                $t->unsignedBigInteger('warehouse_id')->nullable(); // null = every employee
                $t->string('status', 10)->default('draft'); // draft | final
                $t->unsignedBigInteger('account_id')->nullable(); // salaries are paid from this account
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamp('finalized_at')->nullable();
                $t->timestamps();
                $t->unique(['month', 'warehouse_id']);
            });
        }

        if (!Schema::hasTable('salary_sheet_lines')) {
            Schema::create('salary_sheet_lines', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('salary_sheet_id')->index();
                $t->unsignedBigInteger('employee_id')->index();
                $t->decimal('basic_salary', 12, 2)->default(0);
                $t->unsignedSmallInteger('working_days')->default(0);
                $t->unsignedSmallInteger('absent_days')->default(0);
                $t->decimal('absent_deduction', 12, 2)->default(0);
                $t->unsignedInteger('late_minutes')->default(0);
                $t->unsignedInteger('early_minutes')->default(0);
                $t->decimal('per_minute_rate', 12, 4)->default(0);
                $t->decimal('minute_deduction', 12, 2)->default(0);
                $t->decimal('eid_bonus', 12, 2)->default(0);
                $t->decimal('loan_deduction', 12, 2)->default(0);
                $t->decimal('other_deduction', 12, 2)->default(0);
                $t->decimal('net_pay', 12, 2)->default(0);
                $t->text('details')->nullable(); // JSON: day by day findings for the accountant
                $t->string('note', 255)->nullable();
                $t->unsignedBigInteger('payroll_id')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_sheet_lines');
        Schema::dropIfExists('salary_sheets');
        Schema::dropIfExists('staff_loan_repayments');
        Schema::dropIfExists('staff_loans');
        if (Schema::hasColumn('hrm_settings', 'weekly_off')) {
            Schema::table('hrm_settings', function (Blueprint $t) {
                $t->dropColumn(['weekly_off', 'otp_channel']);
            });
        }
    }
};
