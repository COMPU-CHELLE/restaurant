<?php

namespace App\Http\Requests\Support;

use App\Models\Support\FactusPayService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FactusPayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:SOPORTE ya restringe la ruta
    }

    public function rules(): array
    {
        $editing = $this->route('pay') !== null || $this->filled('id');
        $ignore = $this->route('pay') ?? $this->input('id');

        return [
            'codemp' => ['required', 'integer', Rule::exists('companies', 'codmov')],
            'environment' => ['required', Rule::in(['sandbox', 'production']), function (string $a, mixed $v, \Closure $fail) use ($ignore) {
                $taken = FactusPayService::query()
                    ->where('codemp', $this->input('codemp'))->where('environment', $v)
                    ->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists();

                if ($taken) {
                    $fail('Esa empresa ya tiene una configuración de Factus Pay en este ambiente.');
                }
            }],
            'email' => ['required', 'email', 'max:255'],
            'password' => [$editing ? 'nullable' : 'required', 'string', 'max:1000'],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['codemp' => 'empresa', 'environment' => 'ambiente', 'email' => 'correo', 'password' => 'contraseña'];
    }
}
