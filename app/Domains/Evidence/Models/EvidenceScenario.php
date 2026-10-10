<?php

namespace App\Domains\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceScenario extends Model
{
    protected $fillable = ['code', 'name', 'description', 'context_payload', 'created_by'];

    protected $casts = ['context_payload' => 'array'];

    public function evidenceRecords()
    {
        return $this->hasMany(EvidenceRecord::class);
    }

    public function surveyScenarios()
    {
        return $this->hasMany(SurveyScenario::class);
    }
}
