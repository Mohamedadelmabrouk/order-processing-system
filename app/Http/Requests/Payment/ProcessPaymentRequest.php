<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'gateway' => [
                'required',
                'string',
                Rule::in(['credit_card', 'paypal', 'stripe']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'The order id is required.',
            'order_id.exists' => 'The selected order does not exist.',
            'gateway.required' => 'The payment gateway is required.',
            'gateway.in' => 'The selected payment gateway is invalid.',
        ];
    }
}
