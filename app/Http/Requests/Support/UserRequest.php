<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo restringe role:SOPORTE en la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(trim((string) $this->input('code'))),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'code' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', Password::min(8)],
            'state' => ['boolean'],
            'is_support' => ['boolean'],
            'companies' => ['array'],
            'companies.*.company_id' => ['required', 'integer', 'distinct', Rule::exists('companies', 'codmov')],
            'companies.*.role_id' => ['required', 'integer',
                Rule::exists('roles', 'codmov')->whereIn('code', ['ADMIN', 'USER'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código', 'name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña',
            'companies.*.company_id' => 'empresa', 'companies.*.role_id' => 'rol',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite minúsculas, números, punto, guion y guion bajo.',
            'code.unique' => 'Ya existe un usuario con ese código.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'companies.*.company_id.distinct' => 'No repitas la misma empresa.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
