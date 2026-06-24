<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'delivery_address_id');
    }
}
