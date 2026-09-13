<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreOrder extends Model
{
    use SoftDeletes;

    protected $table = 'pre_orders';

    protected $fillable = [
        'order_no',
        'customer_id',
        'customer_name',
        'customer_phone',
        'from_warehouse_id',
        'to_warehouse_id',
        'product_id',
        'serial_number',
        'price',
        'advance_amount',
        'cash_register_id',
        'account_id',
        'paying_method',
        'status',
        'user_id',
        'sale_id',
        'expected_delivery_date',
        'notes',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function serial()
    {
        return $this->belongsTo(ProductSerial::class, 'serial_number', 'serial_number');
    }

    public function scopeIncoming($query, $warehouseId)
    {
        return $query->where('from_warehouse_id', $warehouseId);
    }

    public function scopeOutgoing($query, $warehouseId)
    {
        return $query->where('to_warehouse_id', $warehouseId);
    }
}
