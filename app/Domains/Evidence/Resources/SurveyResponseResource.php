<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'survey_id' => $this->survey_id,
            'respondent' => $this->whenLoaded('respondent', function () {
                return [
                    'id' => $this->respondent->id,
                    'respondent_code' => $this->respondent->respondent_code,
                    'session_reference' => $this->respondent->session_reference,
                    'profile_payload' => $this->respondent->profile_payload,
                ];
            }),
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'raw_payload' => $this->raw_payload,
            'answers' => SurveyResponseAnswerResource::collection(
                $this->whenLoaded('answers')
            ),
            'simulated_purchase_events' => SimulatedPurchaseEventResource::collection(
                $this->whenLoaded('simulatedPurchaseEvents')
            ),
        ];
    }
}
