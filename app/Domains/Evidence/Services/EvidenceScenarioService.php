<?php

namespace App\Domains\Evidence\Services;

use App\Domains\Evidence\Models\EvidenceScenario;
use Illuminate\Support\Facades\Validator;

/** Internal application service; callers must authorize their own workflow. */
class EvidenceScenarioService
{
    public function create(array $data, ?int $userId): EvidenceScenario
    {
        $validated = Validator::make($data, [
            'code' => ['required', 'string', 'max:100', 'unique:evidence_scenarios,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'context_payload' => ['nullable', 'array'],
        ])->validate();

        return EvidenceScenario::create([...$validated, 'created_by' => $userId]);
    }
}
