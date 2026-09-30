<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLotMovement extends Model
{
    protected $fillable = ['inventory_lot_id', 'inventory_movement_item_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:6'];

    public function lot()
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }

    public function movementItem()
    {
        return $this->belongsTo(InventoryMovementItem::class, 'inventory_movement_item_id');
    }
}
