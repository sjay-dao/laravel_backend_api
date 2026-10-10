<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    protected $fillable = [

        'inventory_object_id',

        'warehouse_id',

        'quantity',

        'reference_type',

        'reference_id',

        'remarks',

        'is_released',

        'created_by',

        'released_by',

        'released_at'

    ];

    protected $casts = [

        'is_released' => 'boolean',

        'released_at' => 'datetime',

    ];

    public function inventoryObject()
    {
        return $this->belongsTo(
            InventoryObject::class
        );
    }
}