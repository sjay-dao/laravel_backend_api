<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovementType extends Model
{
    protected $table = 'inventory_movement_types';

    protected $fillable = [
        'code',
        'name',
        'direction',
        'description',
    ];

    public function movements()
    {
        return $this->hasMany(
            InventoryMovement::class,
            'movement_type_id'
        );
    }
}