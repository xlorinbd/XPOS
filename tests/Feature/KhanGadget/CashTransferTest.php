<?php

namespace Tests\Feature\KhanGadget;

use App\Services\AccountLedger;
use App\Services\MoneyTransferService;
use Illuminate\Validation\ValidationException;

class CashTransferTest extends KgTestCase
{
    private function setUpAccounts(): array
    {
        $branch = $this->branch();
        $warehouse = $this->branch('Test Warehouse', 'warehouse');
        $from = $this->account('Branch Cash', $branch, 100000);
        $to = $this->account('Warehouse Cash', $warehouse, 0);
        return [$from, $to, $this->admin()];
    }

    public function test_sender_is_debited_at_once_and_receiver_only_gets_what_is_accepted(): void
    {
        [$from, $to, $user] = $this->setUpAccounts();
        $service = app(MoneyTransferService::class);
        $ledger = app(AccountLedger::class);

        $t = $service->create(['type_code' => 'branch_to_warehouse', 'from_account_id' => $from->id, 'to_account_id' => $to->id, 'amount' => 30000], $user);

        $this->assertSame('pending', $t->status);
        $this->assertEquals(70000, $ledger->balance($from->fresh()));
        $this->assertEquals(30000, $ledger->inTransit($from->id));
        $this->assertEquals(0, $ledger->balance($to->fresh()), 'receiver gets nothing until the transfer is accepted');

        $t = $service->respond($t, $user, 28000, 'Karim', null, 'Counted 28,000');
        $this->assertEquals(28000, $ledger->balance($to->fresh()));
        $this->assertEquals(70000, $ledger->balance($from->fresh()), 'the refund only counts after the sender accepts it');
        $this->assertEquals(2000, $ledger->inTransit($from->id));

        $service->acceptRefund($t, $user);
        $this->assertEquals(72000, $ledger->balance($from->fresh()));
        $this->assertEquals(0, $ledger->inTransit($from->id));

        // no money was created or lost
        $this->assertEquals(100000, $ledger->balance($from->fresh()) + $ledger->balance($to->fresh()));
    }

    public function test_rejecting_needs_a_reason_and_a_cancelled_transfer_returns_nothing_to_the_receiver(): void
    {
        [$from, $to, $user] = $this->setUpAccounts();
        $service = app(MoneyTransferService::class);

        $t = $service->create(['type_code' => 'branch_to_warehouse', 'from_account_id' => $from->id, 'to_account_id' => $to->id, 'amount' => 5000], $user);

        $this->expectException(ValidationException::class);
        $service->respond($t, $user, 0, 'Karim', null, ''); // rejecting everything without a reason
    }

    public function test_cancelled_transfer_gives_the_money_back_to_the_sender(): void
    {
        [$from, $to, $user] = $this->setUpAccounts();
        $service = app(MoneyTransferService::class);
        $ledger = app(AccountLedger::class);

        $t = $service->create(['type_code' => 'branch_to_warehouse', 'from_account_id' => $from->id, 'to_account_id' => $to->id, 'amount' => 5000], $user);
        $this->assertEquals(95000, $ledger->balance($from->fresh()));

        $service->cancel($t, $user);
        $this->assertEquals(100000, $ledger->balance($from->fresh()));
        $this->assertEquals(0, $ledger->balance($to->fresh()));
    }

    public function test_cannot_send_more_than_the_balance(): void
    {
        [$from, $to, $user] = $this->setUpAccounts();
        $this->expectException(ValidationException::class);
        app(MoneyTransferService::class)->create(['type_code' => 'branch_to_warehouse', 'from_account_id' => $from->id, 'to_account_id' => $to->id, 'amount' => 100001], $user);
    }
}
