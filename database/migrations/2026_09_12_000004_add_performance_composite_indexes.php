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
        Schema::table('product_serials', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status'], 'ps_wh_status_idx');
            $table->index(['product_id', 'warehouse_id', 'status'], 'ps_prod_wh_status_idx');
        });

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->index(['status', 'to_warehouse_id'], 'po_status_to_wh_idx');
            $table->index(['status', 'from_warehouse_id'], 'po_status_from_wh_idx');
        });

        Schema::table('damage_records', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status'], 'dmg_wh_status_idx');
        });

        Schema::table('supplier_rmas', function (Blueprint $table) {
            $table->index(['supplier_id', 'status'], 'rma_sup_status_idx');
            $table->index(['warehouse_id', 'status'], 'rma_wh_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            $table->dropIndex('ps_wh_status_idx');
            $table->dropIndex('ps_prod_wh_status_idx');
        });

        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropIndex('po_status_to_wh_idx');
            $table->dropIndex('po_status_from_wh_idx');
        });

        Schema::table('damage_records', function (Blueprint $table) {
            $table->dropIndex('dmg_wh_status_idx');
        });

        Schema::table('supplier_rmas', function (Blueprint $table) {
            $table->dropIndex('rma_sup_status_idx');
            $table->dropIndex('rma_wh_status_idx');
        });
    }
};
