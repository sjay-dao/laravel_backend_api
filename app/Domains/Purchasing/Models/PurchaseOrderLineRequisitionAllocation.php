<?php

namespace App\Domains\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLineRequisitionAllocation extends Model
{
    protected $fillable = [
        'purchase_order_line_id', 'purchase_requisition_line_id', 'allocated_quantity',
        'base_quantity', 'conversion_factor',
    ];

    protected $casts = [
        'allocated_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6', 'conversion_factor' => 'decimal:6',
    ];

    public function purchaseOrderLine() { return $this->belongsTo(PurchaseOrderLine::class); }
    public function purchaseRequisitionLine() { return $this->belongsTo(PurchaseRequisitionLine::class); }
}
