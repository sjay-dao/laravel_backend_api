<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Reference\Models\Branch;
use App\Domains\Reference\Models\Supplier;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    public const DRAFT = 'draft';
    public const POSTED = 'posted';
    public const REVERSED = 'reversed';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'purchase_order_id', 'receipt_number', 'supplier_id', 'branch_id', 'warehouse_id',
        'received_date', 'received_at', 'delivery_reference', 'status', 'remarks',
        'inspection_notes', 'received_by', 'inventory_movement_id', 'posted_by', 'posted_at',
        'reversed_by', 'reversed_at', 'reversal_reason',
    ];

    protected $casts = [
        'received_date' => 'date', 'received_at' => 'datetime', 'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
    public function postedBy() { return $this->belongsTo(User::class, 'posted_by'); }
    public function inventoryMovement() { return $this->belongsTo(InventoryMovement::class); }
    public function lines() { return $this->hasMany(GoodsReceiptLine::class, 'goods_receipt_id'); }
    public function valuations() { return $this->hasMany(GoodsReceiptLineValuation::class); }
    public function supplierReturns() { return $this->hasMany(SupplierReturn::class); }
}
