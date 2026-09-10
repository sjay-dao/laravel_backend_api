<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Evidence\Models\MarketObservation;
use App\Domains\Evidence\Models\EvidenceReconciliation;
use App\Domains\Evidence\Models\SimulatedPurchaseEvent;
use App\Domains\Evidence\Models\SupplierProductObservation;
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

    public function supplierObservations()
    {
        return $this->hasMany(SupplierProductObservation::class);
    }

    public function marketObservations()
    {
        return $this->hasMany(MarketObservation::class);
    }

    public function simulatedPurchaseEvents()
    {
        return $this->hasMany(SimulatedPurchaseEvent::class);
    }

    public function evidenceReconciliations()
    {
        return $this->hasMany(EvidenceReconciliation::class);
    }
}
