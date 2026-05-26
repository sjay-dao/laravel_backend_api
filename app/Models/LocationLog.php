<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationLog extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'latitude',
        'longitude',
        'status',
        'remarks',
        'recorded_at',
    ];

public function order()
{
    return $this->belongsTo(Order::class);
}
}
