<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_key' => $this->question_key,
            'prompt' => $this->prompt,
            'question_type' => $this->question_type,
            'is_required' => $this->is_required,
            'display_order' => $this->display_order,
            'configuration' => $this->configuration,
            'options' => SurveyQuestionOptionResource::collection(
                $this->whenLoaded('options')
            ),
        ];
    }
}
