<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pre_orders', 'kind')) {
            Schema::table('pre_orders', function (Blueprint $t) {
                // pre_order = fetched from another branch; pre_booked = kept aside in the same branch
                $t->string('kind', 20)->default('pre_order')->after('order_no');
                $t->decimal('advance_refund_amount', 12, 2)->default(0);
                $t->unsignedBigInteger('advance_refund_account_id')->nullable();
                $t->string('advance_refund_note', 255)->nullable();
                $t->timestamp('advance_refunded_at')->nullable();
                $t->unsignedBigInteger('advance_refunded_by')->nullable();
                $t->string('cancel_reason', 255)->nullable();
            });
        }
        if (!Schema::hasColumn('payments', 'pre_order_id')) {
            Schema::table('payments', function (Blueprint $t) {
                $t->unsignedBigInteger('pre_order_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pre_orders', 'kind')) {
            Schema::table('pre_orders', function (Blueprint $t) {
                $t->dropColumn(['kind', 'advance_refund_amount', 'advance_refund_account_id', 'advance_refund_note', 'advance_refunded_at', 'advance_refunded_by', 'cancel_reason']);
            });
        }
        if (Schema::hasColumn('payments', 'pre_order_id')) {
            Schema::table('payments', function (Blueprint $t) {
                $t->dropColumn('pre_order_id');
            });
        }
    }
};
