<?php

namespace App\Domains\Sales\Models;


use Illuminate\Database\Eloquent\Model;
use App\Domains\Reference\Models\Customer;
use App\Domains\Reference\Models\Reference;
use App\Domains\System\Models\Branch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no',
        'customer_id',
        'branch_id',
        'order_date',
        'requested_delivery_date',
        'status_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'remarks',
    ];

    protected $casts = [
        'order_date' => 'date',
        'requested_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Reference::class);
    }
}