<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'contact_person',
        'contact_number',
        'address_line_1',
        'address_line_2',
        'barangay_id',
        'postal_code',
        'latitude',
        'longitude',
        'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'delivery_address_id');
    }

     public function barangay()
    {
        return $this->belongsTo(PsgcBarangay::class, 'barangay_id');
    }
}
