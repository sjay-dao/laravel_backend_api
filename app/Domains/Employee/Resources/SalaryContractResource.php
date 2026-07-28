<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'employee_id' => $this->employee_id,

            'employee' => [
                'id' => $this->employee?->id,
                'employee_no' => $this->employee?->employee_no,
                'name' => trim(
                    ($this->employee?->first_name ?? '') . ' ' .
                    ($this->employee?->last_name ?? '')
                ),
            ],

            'pay_basis' => $this->pay_basis,

            'salary_rate' => (float) $this->salary_rate,

            'effective_from' => optional($this->effective_from)
                ->format('Y-m-d'),

            'effective_to' => optional($this->effective_to)
                ->format('Y-m-d'),

            'is_active' => (bool) $this->is_active,

            'remarks' => $this->remarks,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}