<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KgNotification extends Model
{
    protected $fillable = ['user_id', 'kind', 'message', 'url', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
