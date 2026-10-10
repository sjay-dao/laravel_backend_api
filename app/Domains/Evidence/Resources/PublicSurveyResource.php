<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;

class PublicSurveyResource extends SurveyResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'questions' => SurveyQuestionResource::collection(
                $this->whenLoaded('questions')
            ),
            'scenarios' => SurveyScenarioResource::collection(
                $this->whenLoaded('scenarios', fn () => $this->scenarios->where('is_active', true)->values())
            ),
        ];
    }
}
