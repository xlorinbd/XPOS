<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable =[

        "name", "type", "phone", "email", "address", "is_active"
    ];

    public const TYPES = [
        'warehouse' => 'Warehouse',
        'branch' => 'Branch',
        'service_center' => 'Service Center',
    ];

    public function product()
    {
    	return $this->hasMany('App\Models\Product');

    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('qty');
    }

    public function printers()
    {
        return $this->hasMany(Printer::class, 'warehouse_id');
    }

    /**
     * Deactivate warehouse and delete related printers
     */
    public function deactivate()
    {
        // set warehouse inactive
        $this->is_active = false;
        $this->save();
        // HARD delete related printers
        $this->printers()->delete();
    }
}
