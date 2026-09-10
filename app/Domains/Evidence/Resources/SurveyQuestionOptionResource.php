<?php

namespace App\Domains\Evidence\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyQuestionOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_code' => $this->option_code,
            'label' => $this->label,
            'raw_value' => $this->raw_value,
            'display_order' => $this->display_order,
        ];
    }
}
