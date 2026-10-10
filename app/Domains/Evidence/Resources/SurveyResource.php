<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'published_by' => $this->published_by,
            'questions_count' => $this->whenCounted('questions'),
            'responses_count' => $this->whenCounted('responses'),
            'scenarios_count' => $this->whenCounted('scenarios'),
            'questions' => SurveyQuestionResource::collection(
                $this->whenLoaded('questions')
            ),
            'scenarios' => SurveyScenarioResource::collection(
                $this->whenLoaded('scenarios')
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
