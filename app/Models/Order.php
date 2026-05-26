<?php

namespace App\Models;

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
    'total_amount',
    'remarks',
    'cancellation_reason',
    'cancelled_at',
];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
