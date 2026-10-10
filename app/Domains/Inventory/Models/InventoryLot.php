<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Reference\Models\Supplier;
use App\Domains\Sales\Models\SalesOrderLotAllocation;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventoryLot extends Model
{
    protected $fillable = ['inventory_object_id', 'inventory_object_unit_id', 'warehouse_id', 'supplier_id', 'ownership', 'quantity_received', 'quantity_available', 'settlement_cost_cents', 'received_date', 'notes', 'created_by'];

    protected $casts = ['quantity_received' => 'decimal:6', 'quantity_available' => 'decimal:6', 'settlement_cost_cents' => 'integer', 'received_date' => 'date'];

    public function inventoryObject()
    {
        return $this->belongsTo(InventoryObject::class);
    }

    public function inventoryObjectUnit()
    {
        return $this->belongsTo(InventoryObjectUnit::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movementLinks()
    {
        return $this->hasMany(InventoryLotMovement::class);
    }

    public function salesAllocations()
    {
        return $this->hasMany(SalesOrderLotAllocation::class);
    }
}
