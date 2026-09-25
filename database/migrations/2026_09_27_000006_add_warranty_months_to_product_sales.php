<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_sales', 'warranty_months')) {
            Schema::table('product_sales', function (Blueprint $t) {
                // set by the salesman at the counter; null = follow the product's own warranty, 0 = no warranty
                $t->unsignedSmallInteger('warranty_months')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_sales', 'warranty_months')) {
            Schema::table('product_sales', function (Blueprint $t) {
                $t->dropColumn('warranty_months');
            });
        }
    }
};
