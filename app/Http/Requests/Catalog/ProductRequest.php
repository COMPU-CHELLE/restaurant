<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso lo valida el middleware "permission" de la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        $id = $this->route('product') ? (int) $this->route('product') : null;

        return [
            'category_id' => ['required', 'integer',
                Rule::exists('tenant.categories', 'codmov')->where('state', 1)->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('tenant.products', 'code')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'barcode' => ['nullable', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
            'product_type' => ['required', Rule::in(['PRODUCT', 'SERVICE'])],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'categoría', 'code' => 'código', 'barcode' => 'código de barras', 'name' => 'nombre',
            'detail' => 'descripción', 'product_type' => 'tipo', 'sale_price' => 'precio de venta', 'cost_price' => 'costo',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite mayúsculas, números, punto, guion y guion bajo.',
            'code.unique' => 'Ya existe un producto con ese código.',
            'category_id.required' => 'Elige la categoría.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
