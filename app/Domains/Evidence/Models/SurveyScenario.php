<?php

namespace App\Domains\Evidence\Models;

use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use Illuminate\Database\Eloquent\Model;

class SurveyScenario extends Model
{
    protected $fillable = ['evidence_scenario_id', 'survey_id', 'scenario_code', 'inventory_object_id', 'inventory_object_unit_id', 'proposed_price', 'currency_code', 'description', 'is_active'];

    protected $casts = ['proposed_price' => 'decimal:6', 'is_active' => 'boolean'];

    public function evidenceScenario()
    {
        return $this->belongsTo(EvidenceScenario::class);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function inventoryObject()
    {
        return $this->belongsTo(InventoryObject::class);
    }

    public function inventoryObjectUnit()
    {
        return $this->belongsTo(InventoryObjectUnit::class);
    }
}
