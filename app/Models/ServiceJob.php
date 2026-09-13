<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceJob extends Model
{
    use SoftDeletes;

    protected $table = 'service_jobs';

    protected $fillable = [
        'ticket_no',
        'product_serial_id',
        'serial_number',
        'product_id',
        'sale_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'warehouse_id',
        'technician_id',
        'is_warranty_covered',
        'problem_description',
        'condition_notes',
        'accessories_received',
        'technician_notes',
        'status',
        'service_charge',
        'parts_charge',
        'total_cost',
        'paid_amount',
        'paying_method',
        'received_at',
        'completed_at',
        'delivered_at',
        'user_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function serial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['received', 'sent_to_lab', 'under_service', 'ready_for_delivery']);
    }
}
