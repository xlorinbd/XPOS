<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'reference_no', 'shipment_type', 'other_method', 'carrier_name', 'carrier_contact', 'tracking_no',
        'responsible_person', 'shipment_date', 'expected_arrival', 'actual_arrival', 'destination_warehouse_id',
        'status', 'shipping_cost', 'customs_cost', 'additional_cost', 'cost_account_id', 'expense_id',
        'received_at', 'received_by', 'finalized_at', 'finalized_by', 'remarks', 'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'expected_arrival' => 'date',
        'actual_arrival' => 'date',
        'received_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public const TYPES = [
        'cargo' => 'Cargo',
        'hand_carry' => 'Hand Carry',
        'other' => 'Other Method',
    ];

    public const STATUSES = [
        'in_transit' => 'In-Transit / Shipped',
        'received' => 'BD Warehouse Received',
        'final_entry' => 'Added to Stock',
        'cancelled' => 'Cancelled',
    ];

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalLandedCostAttribute(): float
    {
        return (float) $this->shipping_cost + (float) $this->customs_cost + (float) $this->additional_cost;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->shipment_type] ?? ucfirst($this->shipment_type);
    }
}
