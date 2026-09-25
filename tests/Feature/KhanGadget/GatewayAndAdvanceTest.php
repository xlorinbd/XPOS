<?php

namespace Tests\Feature\KhanGadget;

use App\Models\Payment;
use App\Services\AccountLedger;
use Illuminate\Support\Facades\DB;

class GatewayAndAdvanceTest extends KgTestCase
{
    public function test_gateway_money_counts_only_after_it_is_confirmed(): void
    {
        $gateway = $this->account('Payment Gateway');
        $ledger = app(AccountLedger::class);

        $p = Payment::create([
            'payment_reference' => 't-gw', 'user_id' => 1, 'sale_id' => 999999, 'account_id' => $gateway->id, 'amount' => 600,
            'change' => 0, 'paying_method' => 'Credit Card', 'confirm_status' => 'pending',
        ]);

        $this->assertEquals(0, $ledger->balance($gateway->fresh()));
        $this->assertEquals(600, $ledger->pendingConfirmation($gateway->id));

        $p->update(['confirm_status' => 'confirmed', 'confirmed_at' => now()]);
        $this->assertEquals(600, $ledger->balance($gateway->fresh()));
        $this->assertEquals(0, $ledger->pendingConfirmation($gateway->id));
    }

    public function test_preorder_advance_is_in_the_account_and_a_partial_refund_leaves_the_rest(): void
    {
        $branch = $this->branch();
        $cash = $this->account('Branch Cash', $branch);
        $ledger = app(AccountLedger::class);

        Payment::create([
            'payment_reference' => 't-adv', 'user_id' => 1, 'pre_order_id' => 888888, 'account_id' => $cash->id, 'amount' => 2000,
            'change' => 0, 'paying_method' => 'Cash', 'confirm_status' => 'confirmed',
        ]);
        $this->assertEquals(2000, $ledger->balance($cash->fresh()));

        // the order is cancelled and 1,000 of the advance is handed back; 1,000 stays with the shop
        DB::table('pre_orders')->insert([
            'order_no' => 'T-' . uniqid(), 'kind' => 'pre_order', 'customer_name' => 'T', 'customer_phone' => '0', 'from_warehouse_id' => 1,
            'to_warehouse_id' => $branch->id, 'product_id' => 1, 'price' => 50000, 'advance_amount' => 2000, 'status' => 'cancelled', 'user_id' => 1,
            'advance_refund_amount' => 1000, 'advance_refund_account_id' => $cash->id, 'advance_refunded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertEquals(1000, $ledger->balance($cash->fresh()));
    }

    public function test_daily_account_opening_is_the_previous_day_closing(): void
    {
        $branch = $this->branch();
        $cash = $this->account('Branch Cash', $branch);
        $ledger = app(AccountLedger::class);
        $yesterday = now()->subDay()->setTime(15, 0);

        DB::table('incomes')->insert(['reference_no' => 't-i1', 'income_category_id' => 1, 'warehouse_id' => $branch->id, 'account_id' => $cash->id, 'user_id' => 1, 'amount' => 10000, 'created_at' => $yesterday, 'updated_at' => $yesterday]);
        DB::table('expenses')->insert(['reference_no' => 't-e1', 'expense_category_id' => 1, 'warehouse_id' => $branch->id, 'account_id' => $cash->id, 'user_id' => 1, 'amount' => 1500, 'created_at' => now(), 'updated_at' => now()]);

        $controller = app(\App\Http\Controllers\DailyAccountController::class);
        $today = $controller->build($branch->id, now()->startOfDay());
        $yest = $controller->build($branch->id, now()->subDay()->startOfDay());

        $this->assertEquals(10000, $yest['cash_sum']['closing']);
        $this->assertEquals($yest['cash_sum']['closing'], $today['cash_sum']['opening']);
        $this->assertEquals(1500, $today['cash_sum']['out']);
        $this->assertEquals(8500, $today['cash_sum']['closing']);
    }
}
