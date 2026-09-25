<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySheet extends Model
{
    protected $fillable = ['month', 'warehouse_id', 'status', 'account_id', 'created_by', 'finalized_at'];

    protected $casts = ['finalized_at' => 'datetime'];

    public function lines()
    {
        return $this->hasMany(SalarySheetLine::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
