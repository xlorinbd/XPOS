<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_loan_otps')) {
            Schema::create('staff_loan_otps', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('employee_id')->unique();
                $t->string('code_hash', 64);
                $t->unsignedTinyInteger('tries')->default(0);
                $t->timestamp('expires_at');
                $t->timestamp('sent_at');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_loan_otps');
    }
};
