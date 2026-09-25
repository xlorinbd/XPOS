<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable =[
        "account_no", "name", "initial_balance", "total_balance", "note", "is_default", "is_active", "code", "type", "parent_account_id", "is_payment",
        "warehouse_id", "owner_user_id",
    ];

    /** Account kinds used by the transfer rules. Older accounts keep the generic "Bank Account". */
    public const KINDS = [
        'Main Bank' => 'main',
        'Main Mobile Banking' => 'main',
        'Bank Account' => 'main',
        'Payment Gateway' => 'gateway',
        'Branch Cash' => 'branch_cash',
        'Branch Bank' => 'branch_bank',
        'Branch Mobile Wallet' => 'branch_bank',
        'Warehouse Cash' => 'warehouse',
        'Staff Wallet' => 'staff',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    /**
     * The account a payment should land in when the seller did not pick one: cash goes to the branch cash box,
     * card to the gateway, bank / mobile banking to the branch's own account (or the company one).
     */
    public static function defaultFor(?string $method, ?int $warehouseId): ?Account
    {
        $m = strtolower((string) $method);
        $types = match (true) {
            in_array($m, ['cash', '1'], true) => ['Branch Cash', 'Warehouse Cash'],
            in_array($m, ['card', 'credit card', '3'], true) => ['Payment Gateway'],
            str_contains($m, 'bank') => ['Branch Bank', 'Main Bank'],
            $m !== '' => ['Branch Mobile Wallet', 'Main Mobile Banking'],
            default => [],
        };
        foreach ($types as $type) {
            $acc = self::where('is_active', true)->where('type', $type)
                ->when(in_array($type, ['Branch Cash', 'Warehouse Cash', 'Branch Bank', 'Branch Mobile Wallet'], true), fn($q) => $q->where('warehouse_id', $warehouseId))
                ->orderBy('id')->first();
            if ($acc) {
                return $acc;
            }
        }
        return self::where('is_default', true)->first() ?? self::where('is_active', true)->first();
    }

    /** May this account be used for a payment taken in the given branch? (company accounts and that branch's own) */
    public function usableAt(?int $warehouseId): bool
    {
        return $this->is_active && $this->kind !== 'staff' && (empty($this->warehouse_id) || (int) $this->warehouse_id === (int) $warehouseId);
    }

    /** main | branch_cash | branch_bank | warehouse | staff | gateway */
    public function getKindAttribute(): string
    {
        return self::KINDS[$this->type] ?? 'main';
    }

    /** Does this account belong to one of the given groups (branch = branch_cash or branch_bank)? */
    public function inGroup(string $group): bool
    {
        $kind = $this->kind;
        return $group === 'branch' ? in_array($kind, ['branch_cash', 'branch_bank'], true) : $kind === $group;
    }
}
