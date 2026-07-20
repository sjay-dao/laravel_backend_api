<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Models\InventoryObjectUnit;
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
}