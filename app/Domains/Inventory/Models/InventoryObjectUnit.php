<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\Unit;

class InventoryObjectUnit extends Model
{
    protected $table = 'inventory_object_units';

    protected $fillable = [
        'inventory_object_id',
        'unit_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
    ];

    public function inventoryObject()
    {
        return $this->belongsTo(InventoryObject::class,  'inventory_object_id');
    }

    public function unit()
    {
        return $this->belongsTo(
            Unit::class,
            'unit_id'
        );
    }
}