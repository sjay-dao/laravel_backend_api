<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
   protected $fillable = [
    'user_id',
    'order_number',
    'supplier_name',
    'order_date',
    'expected_delivery_date',
    'status',
    'payment_type',
    'payment_status',
    'payment_due_date',
    'external_payment_reference',
    'delivery_address_id',
    'total_amount',
    'remarks',
    'cancellation_reason',
    'cancelled_at',
    'branch_id'
];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function latestLocationLog()
    {
        return $this->hasOne(LocationLog::class)
            ->latestOfMany('recorded_at');
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function locationLogs()
    {
        return $this->hasMany(LocationLog::class);
    }

    public function branch(){
        return $this->belongsTo(Branch::class);
    }
}
