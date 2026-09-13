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
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 191)->unique();
            $table->unsignedInteger('product_serial_id')->nullable();
            $table->string('serial_number', 191)->index();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('sale_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name', 191);
            $table->string('customer_phone', 191);
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('technician_id')->nullable();
            $table->boolean('is_warranty_covered')->default(true);
            $table->text('problem_description');
            $table->text('condition_notes')->nullable();
            $table->text('accessories_received')->nullable();
            $table->text('technician_notes')->nullable();
            $table->enum('status', [
                'received',
                'sent_to_lab',
                'under_service',
                'ready_for_delivery',
                'delivered',
                'rejected',
                'scrapped'
            ])->default('received')->index();
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('parts_charge', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('paying_method', 191)->nullable();
            $table->dateTime('received_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->unsignedInteger('user_id'); // intake cashier
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_jobs');
    }
};
