<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalarySetupResource extends JsonResource
{
    public function toArray($request): array { return ['id' => $this->id, 'basic_rate' => $this->basic_rate, 'rate_type' => $this->rate_type, 'pay_frequency' => $this->pay_frequency, 'currency' => $this->currency, 'allowance_amount' => $this->allowance_amount, 'effective_from' => $this->effective_from?->toDateString(), 'effective_to' => $this->effective_to?->toDateString(), 'remarks' => $this->remarks]; }
}
