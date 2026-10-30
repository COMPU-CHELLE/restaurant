<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
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
        // El id viene crudo de la ruta: los modelos del tenant no se resuelven por binding implícito.
        $id = $this->route('profile') ? (int) $this->route('profile') : null;

        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('tenant.profiles', 'code')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['integer'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'código', 'name' => 'nombre', 'detail' => 'descripción'];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite mayúsculas, números, guion y guion bajo.',
            'code.unique' => 'Ya existe un perfil con ese código.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
