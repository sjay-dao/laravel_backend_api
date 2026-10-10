<?php
namespace App\Domains\Reference\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reference.suppliers.update') ?? false;
    }
    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id;
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('suppliers', 'code')
                    ->ignore($supplierId),
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