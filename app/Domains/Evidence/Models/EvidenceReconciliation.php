<?php

namespace App\Domains\Evidence\Models;

use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class EvidenceReconciliation extends Model
{
    public const RELATIONSHIP_EXACT = 'exact';
    public const RELATIONSHIP_VARIANT_MATCH = 'variant_match';
    public const RELATIONSHIP_CATEGORY_COMPARABLE = 'category_comparable';
    public const RELATIONSHIP_UNRESOLVED = 'unresolved';
    public const RELATIONSHIP_REJECTED = 'rejected';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUPERSEDED = 'superseded';

    public const RELATIONSHIP_TYPES = [
        self::RELATIONSHIP_EXACT,
        self::RELATIONSHIP_VARIANT_MATCH,
        self::RELATIONSHIP_CATEGORY_COMPARABLE,
        self::RELATIONSHIP_UNRESOLVED,
        self::RELATIONSHIP_REJECTED,
    ];

    public const PRODUCT_RELATIONSHIP_TYPES = [
        self::RELATIONSHIP_EXACT,
        self::RELATIONSHIP_VARIANT_MATCH,
        self::RELATIONSHIP_CATEGORY_COMPARABLE,
    ];

    protected $fillable = [
        'evidence_record_id',
        'inventory_object_id',
        'inventory_object_unit_id',
        'relationship_type',
        'reconciliation_status',
        'notes',
        'rationale',
        'confidence',
        'reconciled_by',
        'reconciled_at',
        'superseded_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];

    public function evidenceRecord()
    {
        return $this->belongsTo(EvidenceRecord::class);
    }

    public function inventoryObject()
    {
        return $this->belongsTo(InventoryObject::class);
    }

    public function inventoryObjectUnit()
    {
        return $this->belongsTo(InventoryObjectUnit::class);
    }

    public function reconciler()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
