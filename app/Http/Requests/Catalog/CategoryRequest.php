<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $id = $this->route('category') ? (int) $this->route('category') : null;

        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('tenant.categories', 'code')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
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
            'code.unique' => 'Ya existe una categoría con ese código.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
