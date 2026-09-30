<?php

namespace App\Domains\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(['CASH', 'GCASH', 'BANK_TRANSFER'])],
            'tendered_amount_cents' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:150', Rule::requiredIf(fn () => in_array($this->input('method'), ['GCASH', 'BANK_TRANSFER'], true))],
            'received_at' => ['nullable', 'date'],
        ];
    }
}
