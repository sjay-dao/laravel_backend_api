<?php

namespace App\Domains\Sales\Models;

use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class SalesOrderPayment extends Model
{
    protected $fillable = ['sales_order_id', 'payment_no', 'method', 'tendered_amount_cents', 'applied_amount_cents', 'change_cents', 'reference', 'received_at', 'received_by'];

    protected $casts = [
        'tendered_amount_cents' => 'integer',
        'applied_amount_cents' => 'integer',
        'change_cents' => 'integer',
        'received_at' => 'datetime',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
