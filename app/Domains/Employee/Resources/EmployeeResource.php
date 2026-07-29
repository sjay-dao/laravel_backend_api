<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Reference\Resources\ReferenceResource;
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 
            'employee_no' => $this->employee_no, 
            'first_name' => $this->first_name, 
            'middle_name' => $this->middle_name, 
            'last_name' => $this->last_name, 
            'suffix' => $this->suffix, 
            'full_name' => $this->full_name, 
            'birth_date' => $this->birth_date?->toDateString(), 
            'gender' => $this->gender, 'email' => $this->email, 
            'mobile_number' => $this->mobile_number,
            'phone_number' => $this->phone_number, 
            'address' => $this->address, 
            'employment_status' => new ReferenceResource(
                $this->whenLoaded('employmentStatus')
            ), 
            'hire_date' => $this->hire_date?->toDateString(), 
            'regularization_date' => $this->regularization_date?->toDateString(), 
            'separation_date' => $this->separation_date?->toDateString(), 
            'government_ids' => ['tin' => $this->tin, 
                'sss_number' => $this->sss_number, 
                'philhealth_number' => $this->philhealth_number, 'pagibig_number' => $this->pagibig_number], 
            'bank_information' => ['bank_name' => $this->bank_name, 'account_name' => $this->bank_account_name, 'account_number' => $this->bank_account_number], 
            'notes' => $this->notes, 
            'branch' => $this->whenLoaded('branch'), 
            'department' => $this->whenLoaded('department'), 
            'position' => $this->whenLoaded('position'), 
            'emergency_contacts' => EmergencyContactResource::collection($this->whenLoaded('emergencyContacts')), 
            'salary_setups' => SalarySetupResource::collection($this->whenLoaded('salarySetups')), 
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')), 
            'created_at' => $this->created_at?->toISOString(), 
            'updated_at' => $this->updated_at?->toISOString()];
    }
}
