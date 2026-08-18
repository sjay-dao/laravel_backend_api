<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Reference\Models\Branch;
use App\Domains\Reference\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    public const DRAFT = 'draft';
    public const POSTED = 'posted';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'document_number', 'supplier_id', 'branch_id', 'warehouse_id', 'goods_receipt_id',
        'return_at', 'remarks', 'status', 'inventory_movement_id', 'posted_by', 'posted_at',
        'cancelled_by', 'cancelled_at', 'cancellation_reason', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'return_at' => 'datetime', 'posted_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function goodsReceipt() { return $this->belongsTo(GoodsReceipt::class); }
    public function inventoryMovement() { return $this->belongsTo(InventoryMovement::class); }
    public function lines() { return $this->hasMany(SupplierReturnLine::class); }
}
