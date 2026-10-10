<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id', 'inventory_object_unit_id', 'ordered_quantity', 'base_quantity',
        'conversion_factor', 'unit_price', 'discount_amount', 'tax_amount', 'line_total',
        'promised_date', 'inventory_object_code', 'inventory_object_name', 'unit_code',
        'unit_name', 'description',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6',
        'conversion_factor' => 'decimal:6', 'unit_price' => 'decimal:6',
        'discount_amount' => 'decimal:6', 'tax_amount' => 'decimal:6', 'line_total' => 'decimal:6',
        'promised_date' => 'date',
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function inventoryObjectUnit() { return $this->belongsTo(InventoryObjectUnit::class); }
    public function requisitionAllocations() { return $this->hasMany(PurchaseOrderLineRequisitionAllocation::class); }
    public function goodsReceiptLines() { return $this->hasMany(GoodsReceiptLine::class); }
}
