<?php

namespace App\Domains\Payroll\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}