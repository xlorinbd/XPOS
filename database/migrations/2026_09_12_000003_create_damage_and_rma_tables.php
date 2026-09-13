<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Convert product_serials.status from ENUM to VARCHAR(50) for flexibility
        // and allow 'returned_to_vendor' and 'scrapped'
        DB::statement("ALTER TABLE product_serials MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'available'");

        // 2. Create damage_records table
        Schema::create('damage_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('product_serial_id');
            $table->string('serial_number', 191)->index();
            $table->unsignedInteger('warehouse_id');
            $table->string('damage_reason'); // e.g. Dead, Display Broken, Liquid Damage, Transit Defect
            $table->string('responsible_person')->nullable();
            $table->decimal('damage_cost', 12, 2)->default(0);
            $table->string('status', 50)->default('logged'); // logged, in_rma, resolved, scrapped
            $table->unsignedInteger('user_id');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Create supplier_rmas table
        Schema::create('supplier_rmas', function (Blueprint $table) {
            $table->id();
            $table->string('rma_no', 191)->unique();
            $table->unsignedInteger('supplier_id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedBigInteger('damage_record_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('product_serial_id');
            $table->string('serial_number', 191)->index();
            $table->decimal('purchase_cost', 12, 2)->default(0);
            $table->string('reason');
            $table->string('tracking_number')->nullable();
            $table->enum('status', ['dispatched', 'replaced', 'refunded', 'rejected_returned'])->default('dispatched');
            $table->string('replacement_serial_number', 191)->nullable();
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->decimal('loss_amount', 12, 2)->default(0);
            $table->unsignedInteger('expense_id')->nullable();
            $table->dateTime('dispatched_at');
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedInteger('user_id');
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
        Schema::dropIfExists('supplier_rmas');
        Schema::dropIfExists('damage_records');
    }
};
