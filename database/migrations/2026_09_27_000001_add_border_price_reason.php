<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sales', 'pre_orders'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'border_price_reason')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('border_price_reason')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['sales', 'pre_orders'] as $table) {
            if (Schema::hasColumn($table, 'border_price_reason')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('border_price_reason');
                });
            }
        }
    }
};
