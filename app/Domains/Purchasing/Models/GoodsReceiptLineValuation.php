<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryObject;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLineValuation extends Model
{
    protected $fillable = [
        'goods_receipt_id', 'goods_receipt_item_id', 'inventory_object_id', 'received_quantity',
        'base_quantity', 'conversion_factor', 'unit_cost', 'total_cost', 'valuation_basis',
        'is_inventory_valued', 'recognized_at',
    ];

    protected $casts = [
        'received_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6',
        'conversion_factor' => 'decimal:6', 'unit_cost' => 'decimal:6', 'total_cost' => 'decimal:6',
        'is_inventory_valued' => 'boolean', 'recognized_at' => 'datetime',
    ];

    public function goodsReceipt() { return $this->belongsTo(GoodsReceipt::class); }
    public function goodsReceiptLine() { return $this->belongsTo(GoodsReceiptLine::class, 'goods_receipt_item_id'); }
    public function inventoryObject() { return $this->belongsTo(InventoryObject::class); }
}
