<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use Illuminate\Database\Eloquent\Model;

class SupplierReturnLine extends Model
{
    protected $fillable = [
        'supplier_return_id', 'goods_receipt_item_id', 'inventory_object_unit_id', 'return_quantity',
        'base_quantity', 'conversion_factor', 'unit_cost', 'total_cost', 'inventory_object_code',
        'inventory_object_name', 'unit_code', 'unit_name', 'reason',
    ];

    protected $casts = [
        'return_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6',
        'conversion_factor' => 'decimal:6', 'unit_cost' => 'decimal:6', 'total_cost' => 'decimal:6',
    ];

    public function supplierReturn() { return $this->belongsTo(SupplierReturn::class); }
    public function goodsReceiptLine() { return $this->belongsTo(GoodsReceiptLine::class, 'goods_receipt_item_id'); }
    public function inventoryObjectUnit() { return $this->belongsTo(InventoryObjectUnit::class); }
}
