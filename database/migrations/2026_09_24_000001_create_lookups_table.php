<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 40)->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('match_key')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'name']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('adapter_model_id')->nullable()->after('adapter_condition')->index();
            $table->boolean('is_adapter_item')->default(false)->after('adapter_model_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['adapter_model_id', 'is_adapter_item']);
        });
        Schema::dropIfExists('lookups');
    }
};
