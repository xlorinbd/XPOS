<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 191)->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name', 191);
            $table->string('customer_phone', 191);
            $table->unsignedInteger('from_warehouse_id'); // Source Branch holding stock
            $table->unsignedInteger('to_warehouse_id');   // Destination Branch where customer ordered
            $table->unsignedInteger('product_id');
            $table->string('serial_number', 191)->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->unsignedInteger('cash_register_id')->nullable();
            $table->unsignedInteger('account_id')->nullable();
            $table->string('paying_method', 191)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'processing', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->unsignedInteger('user_id'); // Cashier who booked
            $table->unsignedInteger('sale_id')->nullable(); // Converted Sale ID
            $table->date('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_orders');
    }
};
