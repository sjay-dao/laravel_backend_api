<?php
namespace App\Domains\Reference\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'reference.customers.update'
        ) ?? false;
    }
    public function rules(): array
    {
        $customer = $this->route('customer');
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('customers', 'code')
                    ->ignore($customer?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'contact_person' => [
                'nullable',
                'string',
                'max:255',
            ],
            'contact_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
            ],
            'barangay_id' => [
                'nullable',
                'integer',
                'exists:psgc_barangay,id',
            ],
            'tin' => [
                'nullable',
                'string',
                'max:50',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }
}