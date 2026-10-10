<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Reference\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    public const DRAFT = 'draft';
    public const APPROVED = 'approved';
    public const POSTED = 'posted';
    public const REVERSED = 'reversed';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'document_number', 'supplier_id', 'payment_date', 'currency_code', 'payment_method',
        'cash_bank_account_reference', 'external_reference', 'total_amount', 'remarks', 'status',
        'approved_by', 'approved_at', 'posted_by', 'posted_at', 'reversed_by', 'reversed_at',
        'reversal_reason', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date', 'total_amount' => 'decimal:6', 'approved_at' => 'datetime',
        'posted_at' => 'datetime', 'reversed_at' => 'datetime',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function allocations() { return $this->hasMany(SupplierPaymentAllocation::class); }
}
