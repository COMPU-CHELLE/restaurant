<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo restringe role:SOPORTE en la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'country_code' => strtoupper(trim((string) $this->input('country_code', 'CO'))),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('company')?->codmov;

        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('companies', 'code')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'empnom' => ['required', 'string', 'max:255'],
            'empnit' => ['required', 'string', 'max:30',
                Rule::unique('companies', 'empnit')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'empdig' => ['nullable', 'string', 'max:2'],
            'empadd' => ['nullable', 'string', 'max:255'],
            'emppho' => ['nullable', 'string', 'max:30'],
            'empema' => ['nullable', 'email', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código', 'empnom' => 'nombre', 'empnit' => 'NIT', 'empdig' => 'dígito',
            'empadd' => 'dirección', 'emppho' => 'teléfono', 'empema' => 'correo', 'country_code' => 'país',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite letras mayúsculas, números, guion y guion bajo.',
            'code.unique' => 'Ya existe una empresa con ese código.',
            'empnit.unique' => 'Ya existe una empresa con ese NIT.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
