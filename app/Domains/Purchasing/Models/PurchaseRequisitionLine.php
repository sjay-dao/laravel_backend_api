<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Reference\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionLine extends Model
{
    protected $fillable = [
        'purchase_requisition_id', 'inventory_object_unit_id', 'suggested_supplier_id',
        'requested_quantity', 'base_quantity', 'conversion_factor',
        'inventory_object_code', 'inventory_object_name', 'unit_code', 'unit_name', 'description',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6',
        'conversion_factor' => 'decimal:6',
    ];

    public function requisition() { return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id'); }
    public function inventoryObjectUnit() { return $this->belongsTo(InventoryObjectUnit::class); }
    public function suggestedSupplier() { return $this->belongsTo(Supplier::class, 'suggested_supplier_id'); }
    public function purchaseOrderAllocations() { return $this->hasMany(PurchaseOrderLineRequisitionAllocation::class); }
}
