<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales', 'client_token')) {
            Schema::table('sales', function (Blueprint $t) {
                // set by the POS page; lets a sale that was saved offline be sent again without ever creating it twice
                $t->string('client_token', 64)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'client_token')) {
            Schema::table('sales', function (Blueprint $t) {
                $t->dropColumn('client_token');
            });
        }
    }
};
