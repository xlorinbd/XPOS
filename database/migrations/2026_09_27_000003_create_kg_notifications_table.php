<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kg_notifications')) {
            Schema::create('kg_notifications', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->string('kind', 40)->default('info'); // sale, pre_order, transfer, cash_transfer, stock, warranty, request
                $t->string('message', 500);
                $t->string('url', 500)->nullable();
                $t->timestamp('read_at')->nullable();
                $t->timestamps();
                $t->index(['user_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kg_notifications');
    }
};
