<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'measurement_type',
        'is_base',
    ];

    protected $casts = [
        'is_base' => 'boolean',
    ];

    public function inventoryObjects()
    {
        return $this->hasMany(
            InventoryObject::class,
            'unit_id'
        );
    }

    public function inventoryObjectUnits()
    {
        return $this->hasMany(
            InventoryObjectUnit::class,
            'unit_id'
        );
    }
}