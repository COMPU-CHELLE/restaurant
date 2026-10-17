<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FactusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo restringe role:SOPORTE en la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['base_url' => rtrim(trim((string) $this->input('base_url')), '/')]);
    }

    /** $editing = existe la configuración (los secretos pueden quedar en blanco). */
    public function rules(): array
    {
        $editing = $this->route('factus') !== null || $this->filled('id');

        return [
            'codemp' => ['required', 'integer', Rule::exists('companies', 'codmov')],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'base_url' => ['required', 'url:https', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                $host = parse_url((string) $value, PHP_URL_HOST);

                if (! in_array($host, config('factus.allowed_hosts', []), true)) {
                    $fail('La URL debe ser un servidor oficial de Factus (' . implode(', ', config('factus.allowed_hosts', [])) . ').');
                }
            }],
            'api_version' => ['required', Rule::in(['v1', 'v2'])],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => [$editing ? 'nullable' : 'required', 'string', 'max:1000'],
            'username' => ['required', 'string', 'max:255'],
            'password' => [$editing ? 'nullable' : 'required', 'string', 'max:1000'],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'codemp' => 'empresa', 'environment' => 'ambiente', 'base_url' => 'URL', 'api_version' => 'versión',
            'client_id' => 'client id', 'client_secret' => 'client secret', 'username' => 'usuario', 'password' => 'contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'base_url.url' => 'La URL debe empezar por https://',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
