<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyTransfer extends Model
{
    protected $fillable = [
        'reference_no', 'from_account_id', 'to_account_id', 'amount', 'created_at',
        'type_code', 'status', 'accepted_amount', 'refund_amount', 'refund_status', 'refund_completed_at', 'refund_accepted_by',
        'from_warehouse_id', 'to_warehouse_id', 'third_party_name', 'third_party_details',
        'expense_category_id', 'expense_id', 'carried_by', 'note', 'created_by',
        'responded_by', 'responded_at', 'received_by_name', 'response_note', 'cancelled_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'refund_completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Ten kinds of transfer. "approval" = the receiving side must accept (or reject with a reason).
     * Everything else completes at once. from/to list the account groups that may be used.
     */
    public const TYPES = [
        'branch_cash_to_main' => ['label' => 'Branch Cash to Main Account', 'approval' => false, 'from' => ['branch_cash'], 'to' => ['main']],
        'branch_cash_to_atm' => ['label' => 'Branch Cash to ATM / Bank Deposit', 'approval' => false, 'from' => ['branch_cash'], 'to' => ['main', 'branch_bank', 'staff']],
        'staff_to_kg_bank' => ['label' => 'Staff Bank / Wallet to KG Bank', 'approval' => false, 'from' => ['staff'], 'to' => ['main']],
        'staff_to_third_party' => ['label' => 'Staff Bank / Wallet to Third Party (KG Refer)', 'approval' => false, 'from' => ['staff'], 'to' => null],
        'staff_to_branch_cash' => ['label' => 'Staff Bank / Wallet to Branch Cash', 'approval' => false, 'from' => ['staff'], 'to' => ['branch_cash']],
        'staff_to_expense' => ['label' => 'Staff Bank / Wallet to Expense', 'approval' => false, 'from' => ['staff'], 'to' => 'expense'],
        'branch_to_branch' => ['label' => 'Branch to Branch', 'approval' => true, 'from' => ['branch'], 'to' => ['branch']],
        'branch_to_warehouse' => ['label' => 'Branch to Warehouse', 'approval' => true, 'from' => ['branch'], 'to' => ['warehouse']],
        'warehouse_to_branch' => ['label' => 'Warehouse to Branch', 'approval' => true, 'from' => ['warehouse'], 'to' => ['branch']],
        'third_party' => ['label' => 'Third Party Transfer', 'approval' => false, 'from' => ['main', 'branch', 'staff', 'warehouse'], 'to' => null],
    ];

    public const STATUSES = [
        'pending' => 'Awaiting acceptance',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'recorded' => 'Recorded',
        'cancelled' => 'Cancelled',
    ];

    protected static function booted(): void
    {
        // Transfers created outside the workflow (mobile API, old code) are instant: the receiver gets the full amount.
        static::saving(function (MoneyTransfer $t) {
            if ($t->type_code === null && in_array($t->status, [null, 'completed'], true) && (float) $t->accepted_amount == 0.0 && (float) $t->amount > 0) {
                $t->status = 'completed';
                $t->accepted_amount = $t->amount;
            }
        });
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type_code]['label'] ?? 'Transfer';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->refund_status === 'pending') {
            return 'Refund pending';
        }
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
