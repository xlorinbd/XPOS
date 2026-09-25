<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable =[
        "name", "code", "model", "processor", "ram", "storage", "display", "dedicated_graphics", "adapter_condition", "remarks", "adapter_model_id", "is_adapter_item", "product_condition", "type", "slug", "barcode_symbology", "brand_id", "category_id", "unit_id", "purchase_unit_id", "sale_unit_id", "cost", "profit_margin", "profit_margin_type", "price", "discount_price", "last_border_price", "wholesale_price", "qty", "alert_quantity", "daily_sale_objective", "promotion", "promotion_price", "starting_date", "last_date", "tax_id", "tax_method", "image", "file", "is_embeded", "is_batch", "is_variant", "is_diffPrice", "is_imei", "featured", "product_list", "variant_list", "qty_list", "price_list", "product_details", "short_description", "specification", "related_products", "is_addon", "extras", "menu_type", "variant_option", "variant_value", "is_active", "is_online", "kitchen_id", "in_stock", "track_inventory", "is_sync_disable", "woocommerce_product_id","woocommerce_media_id","tags","meta_title","meta_description", "warranty", "guarantee", "warranty_type", "guarantee_type","wastage_percent","combo_unit_id","production_cost","is_recipe"
    ];

    public function serials()
    {
        return $this->hasMany(ProductSerial::class, 'product_id');
    }

    public function availableSerials()
    {
        return $this->hasMany(ProductSerial::class, 'product_id')->where('status', 'available');
    }

    public function getProductConditionLabelAttribute()
    {
        $labels = [
            'used' => 'Used',
            'open_box' => 'Open Box',
            'brand_new' => 'Brand New (Intact)',
            'box_opened' => 'Box Opend (Brand New Just Box Open)',
        ];

        return $labels[$this->product_condition] ?? ucfirst(str_replace('_', ' ', $this->product_condition ?? ''));
    }

    /**
     * Synchronize product_warehouse.qty and products.qty with available serial count
     */
    public function syncSerialStock(?int $warehouseId = null)
    {
        if (!$this->is_imei && $this->serials()->count() === 0) {
            return;
        }

        if ($warehouseId) {
            $warehouses = [$warehouseId];
        } else {
            $warehouses = $this->serials()->distinct()->pluck('warehouse_id')->filter()->toArray();
            if (empty($warehouses)) {
                $warehouses = \DB::table('product_warehouse')->where('product_id', $this->id)->pluck('warehouse_id')->toArray();
            }
        }

        foreach ($warehouses as $wId) {
            $availableCount = ProductSerial::where('product_id', $this->id)
                ->where('warehouse_id', $wId)
                ->where('status', 'available')
                ->count();

            $availableSerials = ProductSerial::where('product_id', $this->id)
                ->where('warehouse_id', $wId)
                ->where('status', 'available')
                ->pluck('serial_number')
                ->implode(',');

            $pw = \DB::table('product_warehouse')->where('product_id', $this->id)->where('warehouse_id', $wId)->first();
            if ($pw) {
                \DB::table('product_warehouse')
                    ->where('id', $pw->id)
                    ->update([
                        'qty' => $availableCount,
                        'imei_number' => $availableSerials ?: null,
                    ]);
            } else {
                \DB::table('product_warehouse')->insert([
                    'product_id' => $this->id,
                    'warehouse_id' => $wId,
                    'qty' => $availableCount,
                    'price' => $this->price,
                    'imei_number' => $availableSerials ?: null,
                ]);
            }
        }

        // Global total quantity across all warehouses
        $totalAvailable = ProductSerial::where('product_id', $this->id)
            ->where('status', 'available')
            ->count();

        \DB::table('products')->where('id', $this->id)->update(['qty' => $totalAvailable]);
        $this->qty = $totalAvailable;
    }

    public function category()
    {
    	return $this->belongsTo('App\Models\Category');
    }

    public function brand()
    {
    	return $this->belongsTo('App\Models\Brand');
    }

    public function tax()
    {
        return $this->belongsTo('App\Models\Tax');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\Unit');
    }

    public function variant()
    {
        return $this->belongsToMany('App\Models\Variant', 'product_variants')->withPivot('id', 'item_code', 'additional_cost', 'additional_price', 'qty');
    }

    public function scopeActiveStandard($query)
    {
        return $query->where([
            ['is_active', true],
            ['type', 'standard']
        ]);
    }

    public function scopeActiveFeatured($query)
    {
        return $query->where([
            ['is_active', true],
            ['featured', 1]
        ]);
    }

    public function products()
    {
        return $this->belongsToMany(Warehouse::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class)->withPivot('qty');
    }

    public function purchases()
    {
        return $this->belongsToMany(Purchase::class,'product_purchases')->withPivot('qty','tax','tax_rate','discount','total');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Models\Sale', 'product_sales')->withPivot('qty','product_batch_id', 'return_qty','net_unit_price','tax','discount','tax_rate','total','is_delivered');
    }

    public function returns()
    {
        return $this->belongsToMany('App\Models\ProductReturn', 'product_returns');
    }

    // product review relation
    public function reviews()
    {
        return $this->hasMany(\Modules\Ecommerce\Entities\ProductReview::class,'product_id');
    }

    public function approvedReviews()
    {
        return $this->hasMany(\Modules\Ecommerce\Entities\ProductReview::class)->where('approved', true);
    }

    protected $casts = [
        'profit_margin_type' => 'string',
        'discount_price' => 'decimal:2',
        'last_border_price' => 'decimal:2',
    ];

}
