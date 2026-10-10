<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Reference\Models\Branch;
use App\Domains\Reference\Models\Supplier;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const ISSUED = 'issued';
    public const PARTIALLY_RECEIVED = 'partially_received';
    public const RECEIVED = 'received';
    public const CLOSED = 'closed';
    public const CANCELLED = 'cancelled';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'document_number', 'supplier_id', 'branch_id', 'warehouse_id', 'order_date',
        'expected_delivery_date', 'currency_code', 'payment_terms', 'supplier_reference',
        'remarks', 'status', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'rejection_reason', 'issued_by', 'issued_at',
        'cancelled_by', 'cancelled_at', 'cancellation_reason', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date', 'expected_delivery_date' => 'date',
        'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime',
        'issued_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function lines() { return $this->hasMany(PurchaseOrderLine::class); }
    public function goodsReceipts() { return $this->hasMany(GoodsReceipt::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
