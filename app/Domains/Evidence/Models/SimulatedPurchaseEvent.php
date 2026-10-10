<?php

namespace App\Domains\Evidence\Models;

use App\Domains\Evidence\Services\SimulatedPurchaseProjection;
use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Shared\Analytics\BusinessEventProjection;
use App\Domains\Shared\Analytics\ProjectsBusinessEvent;
use Illuminate\Database\Eloquent\Model;

class SimulatedPurchaseEvent extends Model implements ProjectsBusinessEvent
{
    protected $fillable = ['currency_code', 'unit_snapshot', 'evidence_record_id', 'survey_id', 'survey_respondent_id', 'survey_response_id', 'survey_scenario_id', 'inventory_object_unit_id', 'source_type', 'epistemic_status', 'would_purchase', 'proposed_price', 'simulated_quantity', 'base_quantity', 'conversion_factor', 'frequency_raw', 'estimated_frequency_per_month', 'alternative_inventory_object_id', 'decision_reason', 'derived_payload', 'simulated_at'];

    protected $casts = ['unit_snapshot' => 'array', 'would_purchase' => 'boolean', 'proposed_price' => 'decimal:6', 'simulated_quantity' => 'decimal:6', 'base_quantity' => 'decimal:6', 'conversion_factor' => 'decimal:6', 'estimated_frequency_per_month' => 'decimal:6', 'derived_payload' => 'array', 'simulated_at' => 'datetime'];

    public function toBusinessEventProjection(): BusinessEventProjection
    {
        return app(SimulatedPurchaseProjection::class)->project($this);
    }

    public function evidence()
    {
        return $this->belongsTo(EvidenceRecord::class, 'evidence_record_id');
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function respondent()
    {
        return $this->belongsTo(SurveyRespondent::class, 'survey_respondent_id');
    }

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function scenario()
    {
        return $this->belongsTo(SurveyScenario::class, 'survey_scenario_id');
    }

    public function inventoryObjectUnit()
    {
        return $this->belongsTo(InventoryObjectUnit::class);
    }

    public function alternativeInventoryObject()
    {
        return $this->belongsTo(InventoryObject::class, 'alternative_inventory_object_id');
    }
}
