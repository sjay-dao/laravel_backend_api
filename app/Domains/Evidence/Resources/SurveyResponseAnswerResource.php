<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResponseAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->whenLoaded('question', function () {
                return [
                    'id' => $this->question->id,
                    'question_key' => $this->question->question_key,
                    'prompt' => $this->question->prompt,
                ];
            }),
            'option' => $this->whenLoaded('option', function () {
                return $this->option ? [
                    'id' => $this->option->id,
                    'option_code' => $this->option->option_code,
                    'label' => $this->option->label,
                ] : null;
            }),
            'raw_value' => $this->raw_value,
            'raw_text' => $this->raw_text,
            'answered_at' => $this->answered_at?->toIso8601String(),
        ];
    }
}
