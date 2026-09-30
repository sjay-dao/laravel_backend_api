<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryWholesalePriceTier extends Model
{
    protected $fillable = ['inventory_object_id', 'min_quantity', 'max_quantity', 'unit_price_cents'];

    protected $casts = ['min_quantity' => 'decimal:6', 'max_quantity' => 'decimal:6', 'unit_price_cents' => 'integer'];

    public function inventoryObject()
    {
        return $this->belongsTo(InventoryObject::class);
    }
}
