<?php

namespace App\Http\Requests\Admin\Configuration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo restringe role:ADMIN,SOPORTE en la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        $id = $this->route('register') ? (int) $this->route('register') : null;

        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('tenant.cash_registers', 'code')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'printer_id' => ['nullable', 'integer', Rule::exists('tenant.printers', 'codmov')->where('state', 1)->whereNull('deleted_at')],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'código', 'name' => 'nombre', 'detail' => 'descripción', 'location' => 'ubicación', 'printer_id' => 'impresora'];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite mayúsculas, números, guion y guion bajo.',
            'code.unique' => 'Ya existe un registro con ese código.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
