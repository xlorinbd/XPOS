<?php

namespace Tests\Feature\KhanGadget;

use App\Models\Account;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Shared set-up. Every test runs inside a database transaction that is rolled back, so the shop's data is never touched.
 * Run with:  php artisan test --testsuite=Feature
 */
abstract class KgTestCase extends TestCase
{
    use DatabaseTransactions;

    protected static int $seq = 0;

    protected function branch(string $name = 'Test Branch', string $type = 'branch'): Warehouse
    {
        return Warehouse::forceCreate(['name' => $name . ' ' . ++self::$seq, 'type' => $type, 'address' => 'test', 'phone' => '0', 'is_active' => 1]);
    }

    protected function account(string $type, ?Warehouse $wh = null, float $opening = 0): Account
    {
        return Account::forceCreate([
            'account_no' => 'T-' . uniqid(), 'name' => $type . ' ' . ++self::$seq, 'type' => $type,
            'warehouse_id' => $wh?->id, 'initial_balance' => $opening, 'total_balance' => $opening, 'is_active' => 1, 'is_default' => 0,
        ]);
    }

    protected function admin(): User
    {
        return User::forceCreate([
            'name' => 'Test Admin ' . ++self::$seq, 'email' => 'test' . uniqid() . '@example.com', 'password' => bcrypt('x'),
            'phone' => '0', 'company_name' => 'T', 'role_id' => 1, 'is_active' => 1, 'is_deleted' => 0,
        ]);
    }
}
