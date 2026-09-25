<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',"phone","company_name", "role_id", "biller_id", "warehouse_id", "kitchen_id", "service_staff", "is_active", "is_deleted"
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function isActive()
    {
        return $this->is_active;
    }

    public function branches()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouses', 'user_id', 'warehouse_id');
    }

    public function holiday() {
        return $this->hasMany('App\Models\Holiday');
    }
}
