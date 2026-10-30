<?php

namespace App\Http\Requests\Catalog;

use App\Support\CatalogDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valida un elemento de catálogo según la definición del tipo (config/catalogs.php). */
class CatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo restringe el rol en la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    /** Conexión del catálogo: support para soporte, tenant para la empresa. */
    protected function connection(): string
    {
        return $this->routeIs('soporte.*') ? 'mysql' : 'tenant';
    }

    protected function definition(): array
    {
        $definition = CatalogDefinition::find((string) $this->route('type'));

        abort_if($definition === null, 404);

        return $definition;
    }

    public function rules(): array
    {
        $type = (string) $this->route('type');
        $id = $this->route('item') ? (int) $this->route('item') : null;
        $conn = $this->connection();

        $rules = [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9._-]+$/',
                Rule::unique("{$conn}.{$type}", 'code')->ignore($id, 'codmov')],
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
            'extra' => ['nullable', 'array'],
        ];

        foreach (CatalogDefinition::fields($this->definition()) as $field) {
            $presence = ! empty($field['required']) ? 'required' : 'nullable';

            $fieldRules = match ($field['type']) {
                'number' => [$presence, 'numeric', 'min:' . ($field['min'] ?? 0), 'max:' . ($field['max'] ?? 999999999)],
                'select' => [$presence, Rule::in(array_keys($field['options'] ?? []))],
                'catalog' => [$presence, 'string', 'max:30', Rule::exists("{$conn}.{$field['source']}", 'code')],
                default => [$presence, 'string', 'max:' . ($field['max'] ?? 255)],
            };

            if (! empty($field['unique'])) {
                $fieldRules[] = Rule::unique("{$conn}.{$type}", $field['key'])->ignore($id, 'codmov');
            }

            $rules["extra.{$field['key']}"] = $fieldRules;
        }

        return $rules;
    }

    public function attributes(): array
    {
        $names = ['code' => 'código', 'name' => 'nombre', 'detail' => 'descripción'];

        foreach (CatalogDefinition::fields($this->definition()) as $field) {
            $names["extra.{$field['key']}"] = mb_strtolower($field['label']);
        }

        return $names;
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite mayúsculas, números, punto, guion y guion bajo.',
            'code.unique' => 'Ya existe un elemento con ese código en este catálogo.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
