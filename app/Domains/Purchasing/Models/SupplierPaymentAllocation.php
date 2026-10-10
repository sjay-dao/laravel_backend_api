<?php

namespace App\Domains\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPaymentAllocation extends Model
{
    protected $fillable = ['supplier_payment_id', 'supplier_invoice_id', 'allocated_amount'];

    protected $casts = ['allocated_amount' => 'decimal:6'];

    public function supplierPayment() { return $this->belongsTo(SupplierPayment::class); }
    public function supplierInvoice() { return $this->belongsTo(SupplierInvoice::class); }
}
