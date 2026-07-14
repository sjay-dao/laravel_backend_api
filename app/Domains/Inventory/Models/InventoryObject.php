<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryObject extends Model
{
    use HasFactory;

    protected $table = 'inventory_objects';

    protected $fillable = [
        'code',
        'name',
        'inventory_category_id',
        'unit_id',
        'track_inventory',
        'is_sellable',
        'is_active',
    ];

    protected $casts = [
        'track_inventory' => 'boolean',
        'is_sellable'     => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(
            InventoryCategory::class,
            'inventory_category_id'
        );
    }

    public function baseUnit()
    {
        return $this->belongsTo(
            Unit::class,
            'unit_id'
        );
    }

    public function units()
    {
        return $this->hasMany(
            InventoryObjectUnit::class,
            'inventory_object_id'
        );
    }
}