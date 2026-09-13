<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSerial extends Model
{
    use SoftDeletes;

    protected $table = 'product_serials';

    protected $fillable = [
        'product_id',
        'serial_number',
        'detailed_condition',
        'status',
        'warehouse_id',
        'purchase_id',
        'sale_id',
    ];

    /**
     * Relationship to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relationship to Warehouse
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Relationship to Purchase
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * Relationship to Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /**
     * Scope for available serials ready for sale
     */
    public function scopeAvailable($query, $warehouseId = null)
    {
        $q = $query->where('status', 'available');
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        return $q;
    }

    /**
     * Scope for sold serials
     */
    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    /**
     * Scope for damaged serials
     */
    public function scopeDamaged($query, $warehouseId = null)
    {
        $q = $query->where('status', 'damaged');
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        return $q;
    }

    /**
     * Domain Lifecycle Helpers
     */
    public function markAsSold(int $saleId): bool
    {
        $this->status = 'sold';
        $this->sale_id = $saleId;
        return $this->save();
    }

    public function markAsAvailable(?int $warehouseId = null): bool
    {
        $this->status = 'available';
        if ($warehouseId) {
            $this->warehouse_id = $warehouseId;
        }
        $this->sale_id = null;
        return $this->save();
    }

    public function markAsTransferring(): bool
    {
        $this->status = 'transferring';
        return $this->save();
    }

    public function markAsUnderService(): bool
    {
        $this->status = 'under_service';
        return $this->save();
    }

    public function markAsDamaged(): bool
    {
        $this->status = 'damaged';
        return $this->save();
    }

    public function markAsReturnedToVendor(): bool
    {
        $this->status = 'returned_to_vendor';
        return $this->save();
    }

    public function markAsCancelled(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }
}
