<?php

namespace App\Observers;

use App\Models\ProductSerial;

class ProductSerialObserver
{
    /**
     * Handle the ProductSerial "created" event.
     */
    public function created(ProductSerial $serial): void
    {
        if ($serial->product) {
            $serial->product->syncSerialStock($serial->warehouse_id);
        }
    }

    /**
     * Handle the ProductSerial "updated" event.
     */
    public function updated(ProductSerial $serial): void
    {
        if ($serial->product) {
            $serial->product->syncSerialStock($serial->warehouse_id);

            // If warehouse was changed, sync the previous warehouse as well
            if ($serial->isDirty('warehouse_id') && $serial->getOriginal('warehouse_id')) {
                $serial->product->syncSerialStock($serial->getOriginal('warehouse_id'));
            }
        }
    }

    /**
     * Handle the ProductSerial "deleted" event.
     */
    public function deleted(ProductSerial $serial): void
    {
        if ($serial->product) {
            $serial->product->syncSerialStock($serial->warehouse_id);
        }
    }

    /**
     * Handle the ProductSerial "restored" event.
     */
    public function restored(ProductSerial $serial): void
    {
        if ($serial->product) {
            $serial->product->syncSerialStock($serial->warehouse_id);
        }
    }
}
