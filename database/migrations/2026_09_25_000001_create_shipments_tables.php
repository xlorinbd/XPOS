<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference_no')->unique();
            $table->string('shipment_type', 20)->default('cargo');      // cargo | hand_carry | other
            $table->string('other_method')->nullable();
            $table->string('carrier_name')->nullable();                // cargo company or hand-carry person (may be external)
            $table->string('carrier_contact')->nullable();
            $table->string('tracking_no')->nullable();
            $table->string('responsible_person')->nullable();
            $table->date('shipment_date')->nullable();
            $table->date('expected_arrival')->nullable();
            $table->date('actual_arrival')->nullable();
            $table->unsignedInteger('destination_warehouse_id');
            $table->string('status', 20)->default('in_transit')->index(); // in_transit | received | final_entry | cancelled
            $table->decimal('shipping_cost', 14, 2)->default(0);
            $table->decimal('customs_cost', 14, 2)->default(0);
            $table->decimal('additional_cost', 14, 2)->default(0);
            $table->unsignedInteger('cost_account_id')->nullable();
            $table->unsignedInteger('expense_id')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->unsignedInteger('received_by')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->unsignedInteger('finalized_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('shipment_id')->index();
            $table->unsignedInteger('purchase_id')->index();
            $table->unsignedInteger('product_purchase_id')->index();
            $table->unsignedInteger('product_id')->index();
            $table->decimal('qty_shipped', 14, 2)->default(0);
            $table->decimal('qty_received', 14, 2)->default(0);
            $table->decimal('qty_finalized', 14, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
        });

        Schema::table('product_serials', function (Blueprint $table) {
            $table->decimal('purchase_cost', 14, 2)->nullable()->after('detailed_condition');
            $table->decimal('landed_cost', 14, 2)->default(0)->after('purchase_cost');
            $table->decimal('extra_cost', 14, 2)->default(0)->after('landed_cost');
            $table->unsignedInteger('shipment_id')->nullable()->after('purchase_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            $table->dropColumn(['purchase_cost', 'landed_cost', 'extra_cost', 'shipment_id']);
        });
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
    }
};
