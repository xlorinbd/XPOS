<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    protected $fillable = [
        'shipment_id', 'purchase_id', 'product_purchase_id', 'product_id',
        'qty_shipped', 'qty_received', 'qty_finalized', 'remarks',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function productPurchase()
    {
        return $this->belongsTo(ProductPurchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
