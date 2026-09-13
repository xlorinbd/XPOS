<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierRma extends Model
{
    use SoftDeletes;

    protected $table = 'supplier_rmas';

    protected $fillable = [
        'rma_no',
        'supplier_id',
        'warehouse_id',
        'damage_record_id',
        'product_id',
        'product_serial_id',
        'serial_number',
        'purchase_cost',
        'reason',
        'tracking_number',
        'status',
        'replacement_serial_number',
        'refund_amount',
        'loss_amount',
        'expense_id',
        'dispatched_at',
        'resolved_at',
        'user_id',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function serial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }

    public function damageRecord()
    {
        return $this->belongsTo(DamageRecord::class, 'damage_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }
}
