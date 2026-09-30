<?php

namespace App\Domains\Sales\Models;

use App\Domains\Inventory\Models\InventoryLot;
use Illuminate\Database\Eloquent\Model;

class SalesOrderLotAllocation extends Model
{
    protected $fillable = ['sales_order_item_id', 'inventory_lot_id', 'quantity', 'settlement_cost_cents'];

    protected $casts = ['quantity' => 'decimal:6', 'settlement_cost_cents' => 'integer'];

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function inventoryLot()
    {
        return $this->belongsTo(InventoryLot::class);
    }
}
