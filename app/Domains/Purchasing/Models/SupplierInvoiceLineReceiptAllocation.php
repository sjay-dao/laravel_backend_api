<?php

namespace App\Domains\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceLineReceiptAllocation extends Model
{
    protected $fillable = [
        'supplier_invoice_line_id', 'goods_receipt_item_id', 'matched_quantity', 'base_quantity',
        'applied_unit_cost', 'applied_amount', 'price_variance_amount',
    ];

    protected $casts = [
        'matched_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6',
        'applied_unit_cost' => 'decimal:6', 'applied_amount' => 'decimal:6',
        'price_variance_amount' => 'decimal:6',
    ];

    public function supplierInvoiceLine() { return $this->belongsTo(SupplierInvoiceLine::class); }
    public function goodsReceiptLine() { return $this->belongsTo(GoodsReceiptLine::class, 'goods_receipt_item_id'); }
}
