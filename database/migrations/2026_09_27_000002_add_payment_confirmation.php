<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'confirm_status')) {
            Schema::table('payments', function (Blueprint $t) {
                // 'confirmed' = counted in the account; 'pending' = card/gateway money not yet settled
                $t->string('confirm_status', 20)->default('confirmed')->index();
                $t->unsignedBigInteger('confirmed_by')->nullable();
                $t->timestamp('confirmed_at')->nullable();
                $t->string('confirm_note')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'confirm_status')) {
            Schema::table('payments', function (Blueprint $t) {
                $t->dropColumn(['confirm_status', 'confirmed_by', 'confirmed_at', 'confirm_note']);
            });
        }
    }
};
