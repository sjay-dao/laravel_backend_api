<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeScheduleAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'employee' => $this->employee,

            'schedule' => $this->schedule,

            'effective_from' => $this->effective_from,

            'effective_to' => $this->effective_to,

            'is_active' => $this->is_active,

            'remarks' => $this->remarks,

            'assigned_by' => $this->assignedBy,

            'created_at' => $this->created_at,

        ];
    }
}