<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Inventory\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLine extends Model
{
    protected $table = 'goods_receipt_items';

    protected $fillable = [
        'goods_receipt_id', 'purchase_order_line_id', 'inventory_object_id',
        'inventory_object_unit_id', 'quantity', 'base_quantity', 'conversion_factor',
        'rejected_quantity', 'damaged_quantity', 'unit_id', 'unit_cost', 'total_cost',
        'inventory_object_code', 'inventory_object_name', 'unit_code', 'unit_name', 'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:6', 'base_quantity' => 'decimal:6', 'conversion_factor' => 'decimal:6',
        'rejected_quantity' => 'decimal:6', 'damaged_quantity' => 'decimal:6',
        'unit_cost' => 'decimal:6', 'total_cost' => 'decimal:6',
    ];

    public function goodsReceipt() { return $this->belongsTo(GoodsReceipt::class); }
    public function purchaseOrderLine() { return $this->belongsTo(PurchaseOrderLine::class); }
    public function inventoryObject() { return $this->belongsTo(InventoryObject::class); }
    public function inventoryObjectUnit() { return $this->belongsTo(InventoryObjectUnit::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function valuation() { return $this->hasOne(GoodsReceiptLineValuation::class, 'goods_receipt_item_id'); }
    public function invoiceAllocations() { return $this->hasMany(SupplierInvoiceLineReceiptAllocation::class, 'goods_receipt_item_id'); }
    public function returnLines() { return $this->hasMany(SupplierReturnLine::class, 'goods_receipt_item_id'); }
}
