<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceLine extends Model
{
    protected $fillable = [
        'supplier_invoice_id', 'inventory_object_unit_id', 'description', 'account_treatment',
        'quantity', 'base_quantity', 'conversion_factor', 'unit_price', 'discount_amount',
        'tax_amount', 'line_total', 'inventory_object_code', 'inventory_object_name',
        'unit_code', 'unit_name',
    ];

    protected $casts = [
        'quantity' => 'decimal:6', 'base_quantity' => 'decimal:6', 'conversion_factor' => 'decimal:6',
        'unit_price' => 'decimal:6', 'discount_amount' => 'decimal:6',
        'tax_amount' => 'decimal:6', 'line_total' => 'decimal:6',
    ];

    public function supplierInvoice() { return $this->belongsTo(SupplierInvoice::class); }
    public function inventoryObjectUnit() { return $this->belongsTo(InventoryObjectUnit::class); }
    public function receiptAllocations() { return $this->hasMany(SupplierInvoiceLineReceiptAllocation::class); }
}
