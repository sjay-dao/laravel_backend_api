<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeTransactionResource extends JsonResource
{
    public function toArray($request): array { return ['id' => $this->id, 'transaction_no' => $this->transaction_no, 'employee_id' => $this->employee_id, 'employee' => $this->whenLoaded('employee'), 'type' => $this->type, 'transaction_date' => $this->transaction_date?->toDateString(), 'amount' => $this->amount, 'ledger_effect' => $this->ledger_effect, 'currency' => $this->currency, 'reference_type' => $this->reference_type, 'reference_no' => $this->reference_no, 'remarks' => $this->remarks, 'status' => $this->status, 'voided_at' => $this->voided_at?->toISOString(), 'created_at' => $this->created_at?->toISOString()]; }
}
