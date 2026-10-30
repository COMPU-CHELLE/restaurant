<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso (…productos.prices) lo valida el middleware de la ruta
    }

    public function rules(): array
    {
        return [
            'price_type' => ['required', Rule::in(['SALE', 'COST'])],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ];
    }

    public function attributes(): array
    {
        return ['price_type' => 'tipo de precio', 'price' => 'precio'];
    }
}
