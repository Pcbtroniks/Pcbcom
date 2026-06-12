<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'min:1'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
