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
        Schema::table('products', function (Blueprint $table) {
            $table->string('model')->nullable()->after('code');
            $table->string('processor')->nullable()->after('model');
            $table->string('ram')->nullable()->after('processor');
            $table->string('storage')->nullable()->after('ram');
            $table->string('display')->nullable()->after('storage');
            $table->string('dedicated_graphics')->nullable()->after('display');
            $table->string('adapter_condition')->nullable()->after('dedicated_graphics');
            $table->enum('product_condition', ['used', 'open_box', 'brand_new', 'box_opened'])->nullable()->after('adapter_condition');
            $table->decimal('discount_price', 12, 2)->nullable()->after('price');
            $table->decimal('last_border_price', 12, 2)->nullable()->after('discount_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'model',
                'processor',
                'ram',
                'storage',
                'display',
                'dedicated_graphics',
                'adapter_condition',
                'product_condition',
                'discount_price',
                'last_border_price',
            ]);
        });
    }
};
