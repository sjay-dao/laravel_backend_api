<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Sales\Models\SalesOrderLotAllocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovementItem extends Model
{
    protected $table = 'inventory_movement_items';

    protected $fillable = [
        'inventory_movement_id',
        'inventory_object_unit_id',
        'quantity',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function movement()
    {
        return $this->belongsTo(
            InventoryMovement::class,
            'inventory_movement_id'
        );
    }

    public function inventoryObjectUnit(): BelongsTo
    {
        return $this->belongsTo(
            InventoryObjectUnit::class,
            'inventory_object_unit_id'
        );
    }

    public function lotLink()
    {
        return $this->hasOne(InventoryLotMovement::class);
    }

    public function salesOrderLotAllocation()
    {
        return $this->hasOne(SalesOrderLotAllocation::class);
    }
}
