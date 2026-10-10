<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Reference\Models\Supplier;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    public const DRAFT = 'draft';
    public const MATCHED = 'matched';
    public const APPROVED = 'approved';
    public const POSTED = 'posted';
    public const PARTIALLY_PAID = 'partially_paid';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'document_number', 'supplier_id', 'supplier_invoice_number', 'invoice_date', 'due_date',
        'currency_code', 'subtotal', 'discount_total', 'tax_total', 'total_amount',
        'allow_without_receipt', 'receipt_exception_reason', 'status', 'matched_by', 'matched_at',
        'approved_by', 'approved_at', 'posted_by', 'posted_at', 'cancelled_by', 'cancelled_at',
        'cancellation_reason', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:6',
        'discount_total' => 'decimal:6', 'tax_total' => 'decimal:6', 'total_amount' => 'decimal:6',
        'allow_without_receipt' => 'boolean', 'matched_at' => 'datetime', 'approved_at' => 'datetime',
        'posted_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function lines() { return $this->hasMany(SupplierInvoiceLine::class); }
    public function paymentAllocations() { return $this->hasMany(SupplierPaymentAllocation::class); }
    public function matchedBy() { return $this->belongsTo(User::class, 'matched_by'); }
}
