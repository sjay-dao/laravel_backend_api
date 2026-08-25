<?php

namespace App\Domains\Sales\Models;


use Illuminate\Database\Eloquent\Model;
use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\Unit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'inventory_id',
        'unit_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'line_total',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(InventoryObject::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}