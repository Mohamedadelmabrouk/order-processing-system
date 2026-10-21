<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'nullable', 'integer', 'exists:orders,id'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in([
                Payment::STATUS_PENDING,
                Payment::STATUS_SUCCESSFUL,
                Payment::STATUS_FAILED,
            ])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
