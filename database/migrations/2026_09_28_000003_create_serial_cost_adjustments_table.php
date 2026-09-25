<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('serial_cost_adjustments')) {
            Schema::create('serial_cost_adjustments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('product_serial_id')->index();
                $t->decimal('amount', 12, 2); // + adds to the unit's cost (repair, upgrade), - takes off (removed accessory / downgrade)
                $t->string('reason', 255);
                $t->unsignedBigInteger('user_id');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_cost_adjustments');
    }
};
