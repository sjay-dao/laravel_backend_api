<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\Unit;


class InventoryMovementItem extends Model
{
    protected $table = 'inventory_movement_items';

    protected $fillable = [
        'inventory_movement_id',
        'inventory_object_id',
        'unit_id',
        'quantity',
        'unit_conversion_factor',
        'base_quantity',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_conversion_factor' => 'decimal:6',
        'base_quantity' => 'decimal:6',
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

    public function inventoryObject()
    {
        return $this->belongsTo(
            InventoryObject::class,
            'inventory_object_id'
        );
    }

    public function unit()
    {
        return $this->belongsTo(
            Unit::class,
            'unit_id'
        );
    }
}