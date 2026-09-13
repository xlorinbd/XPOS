<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DamageRecord extends Model
{
    use SoftDeletes;

    protected $table = 'damage_records';

    protected $fillable = [
        'product_id',
        'product_serial_id',
        'serial_number',
        'warehouse_id',
        'damage_reason',
        'responsible_person',
        'damage_cost',
        'status',
        'user_id',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function serial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rma()
    {
        return $this->hasOne(SupplierRma::class, 'damage_record_id');
    }
}
