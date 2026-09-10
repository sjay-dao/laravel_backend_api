<?php

namespace App\Domains\Evidence\Models;

use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class EvidenceRecord extends Model
{
    protected $fillable = ['evidence_scenario_id', 'inventory_object_id', 'source_type', 'epistemic_status', 'collection_method', 'source_reference', 'source_entity_type', 'source_entity_id', 'observed_at', 'recorded_at', 'location_name', 'raw_payload', 'context_payload', 'notes', 'collected_by', 'import_key'];

    protected $casts = ['observed_at' => 'datetime', 'recorded_at' => 'datetime', 'raw_payload' => 'array', 'context_payload' => 'array'];

    public function evidenceScenario()
    {
        return $this->belongsTo(EvidenceScenario::class);
    }

    public function inventoryObject()
    {
        return $this->belongsTo(InventoryObject::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function supplierObservation()
    {
        return $this->hasOne(SupplierProductObservation::class);
    }

    public function marketObservation()
    {
        return $this->hasOne(MarketObservation::class);
    }

    public function simulatedPurchaseEvent()
    {
        return $this->hasOne(SimulatedPurchaseEvent::class);
    }

    public function reconciliations()
    {
        return $this->hasMany(EvidenceReconciliation::class)->latest('reconciled_at')->latest('id');
    }

    public function currentReconciliation()
    {
        return $this->hasOne(EvidenceReconciliation::class)->where('reconciliation_status', EvidenceReconciliation::STATUS_ACTIVE);
    }
}
