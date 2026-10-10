<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Reference\Models\Branch;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisition extends Model
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';
    public const PARTIALLY_ORDERED = 'partially_ordered';
    public const ORDERED = 'ordered';
    public const CLOSED = 'closed';

    protected $fillable = [
        'document_number', 'branch_id', 'requester_id', 'request_date',
        'needed_by_date', 'purpose', 'status', 'submitted_by', 'submitted_at',
        'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
        'rejection_reason', 'cancelled_by', 'cancelled_at',
        'cancellation_reason', 'closed_by', 'closed_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'request_date' => 'date', 'needed_by_date' => 'date',
        'submitted_at' => 'datetime', 'approved_at' => 'datetime',
        'rejected_at' => 'datetime', 'cancelled_at' => 'datetime', 'closed_at' => 'datetime',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function lines() { return $this->hasMany(PurchaseRequisitionLine::class); }
    public function submittedBy() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}
