<?php

namespace App\Http\Requests\Admin;

use App\Models\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CompanyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo restringe role:ADMIN,SOPORTE en la ruta
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('code')) {
            $merge['code'] = strtolower(trim((string) $this->input('code')));
        }

        if ($this->has('email')) {
            $merge['email'] = strtolower(trim((string) $this->input('email')));
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;
        $creating = $id === null;
        $isUser = Role::query()->where('codmov', (int) $this->input('role_id'))->value('code') === 'USER';

        return [
            // En edición solo llegan si el usuario es exclusivo de esta empresa.
            'code' => [$creating ? 'required' : 'sometimes', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'code')->ignore($id)],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => [$creating ? 'required' : 'sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$creating ? 'required' : 'nullable', Password::min(8)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'codmov')->whereIn('code', ['ADMIN', 'USER'])],
            'profile_id' => [$isUser ? 'required' : 'nullable', 'integer',
                Rule::exists('tenant.profiles', 'codmov')->where('state', 1)->whereNull('deleted_at')],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código', 'name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña',
            'role_id' => 'rol', 'profile_id' => 'perfil',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite minúsculas, números, punto, guion y guion bajo.',
            'code.unique' => 'Ya existe un usuario con ese código.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'profile_id.required' => 'Elige el perfil del usuario.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
