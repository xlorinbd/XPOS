<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('return_purchases') && !Schema::hasColumn('return_purchases', 'refund_type')) {
            Schema::table('return_purchases', function (Blueprint $t) {
                // refund = the supplier pays money back into an account; credit = the supplier keeps it as credit for the next purchase
                $t->string('refund_type', 10)->default('refund');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('return_purchases', 'refund_type')) {
            Schema::table('return_purchases', function (Blueprint $t) {
                $t->dropColumn('refund_type');
            });
        }
    }
};
