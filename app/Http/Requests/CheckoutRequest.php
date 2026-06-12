<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'array'],
            'shipping_address.name' => ['required', 'string', 'max:120'],
            'shipping_address.phone' => ['required', 'string', 'max:32'],
            'shipping_address.line1' => ['required', 'string', 'max:200'],
            'shipping_address.line2' => ['nullable', 'string', 'max:200'],
            'shipping_address.city' => ['required', 'string', 'max:120'],
            'shipping_address.state' => ['required', 'string', 'max:120'],
            'shipping_address.zip' => ['required', 'string', 'max:16'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.notes' => ['nullable', 'string', 'max:500'],

            'billing_address' => ['nullable', 'array'],
            'billing_address.name' => ['required_with:billing_address', 'string', 'max:120'],
            'billing_address.phone' => ['required_with:billing_address', 'string', 'max:32'],
            'billing_address.line1' => ['required_with:billing_address', 'string', 'max:200'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:120'],
            'billing_address.state' => ['required_with:billing_address', 'string', 'max:120'],
            'billing_address.zip' => ['required_with:billing_address', 'string', 'max:16'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'size:2'],

            'payment_method' => ['nullable', 'string', 'in:null,stripe,transfer,credit'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }
}
