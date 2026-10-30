<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo restringe role:ADMIN,SOPORTE en la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper(trim((string) $this->input('currency', 'COP'))),
            'invoice_prefix' => strtoupper(trim((string) $this->input('invoice_prefix'))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'restaurant_name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:30'],
            'invoice_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'tax_percentage' => ['required', 'numeric', 'between:0,100'],
            // Sin SVG: puede llevar scripts. Máximo 2 MB.
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'restaurant_name' => 'nombre del restaurante', 'business_name' => 'razón social', 'tax_id' => 'NIT',
            'invoice_prefix' => 'prefijo de facturación', 'phone' => 'teléfono', 'email' => 'correo',
            'address' => 'dirección', 'city' => 'ciudad', 'country' => 'país', 'currency' => 'moneda',
            'timezone' => 'zona horaria', 'tax_percentage' => 'impuesto', 'logo' => 'logo',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_prefix.regex' => 'El prefijo solo admite letras mayúsculas y números.',
            'logo.mimes' => 'El logo debe ser PNG, JPG o WEBP.',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
