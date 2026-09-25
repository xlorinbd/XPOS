<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable()->index();   // branch / warehouse that owns the account
            $table->unsignedInteger('owner_user_id')->nullable()->index();  // staff member holding a Staff Wallet
        });

        Schema::table('money_transfers', function (Blueprint $table) {
            $table->integer('to_account_id')->nullable()->change();
        });

        Schema::table('money_transfers', function (Blueprint $table) {
            $table->string('type_code', 40)->nullable()->index();
            $table->string('status', 20)->default('completed')->index();      // pending | accepted | rejected | completed | cancelled
            $table->decimal('accepted_amount', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->string('refund_status', 20)->nullable()->index();         // pending | completed
            $table->dateTime('refund_completed_at')->nullable();
            $table->unsignedInteger('refund_accepted_by')->nullable();
            $table->unsignedInteger('from_warehouse_id')->nullable()->index();
            $table->unsignedInteger('to_warehouse_id')->nullable()->index();
            $table->string('third_party_name')->nullable();
            $table->text('third_party_details')->nullable();
            $table->unsignedInteger('expense_category_id')->nullable();
            $table->unsignedInteger('expense_id')->nullable();
            $table->string('carried_by')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('responded_by')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->string('received_by_name')->nullable();
            $table->text('response_note')->nullable();                         // rejection reason (mandatory when anything is rejected)
            $table->dateTime('cancelled_at')->nullable();
        });

        // Transfers made before this workflow existed were instant transfers.
        DB::table('money_transfers')->update([
            'status' => 'completed',
            'accepted_amount' => DB::raw('amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('money_transfers', function (Blueprint $table) {
            $table->dropColumn([
                'type_code', 'status', 'accepted_amount', 'refund_amount', 'refund_status', 'refund_completed_at',
                'refund_accepted_by', 'from_warehouse_id', 'to_warehouse_id', 'third_party_name', 'third_party_details',
                'expense_category_id', 'expense_id', 'carried_by', 'note', 'created_by', 'responded_by', 'responded_at',
                'received_by_name', 'response_note', 'cancelled_at',
            ]);
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['warehouse_id', 'owner_user_id']);
        });
    }
};
