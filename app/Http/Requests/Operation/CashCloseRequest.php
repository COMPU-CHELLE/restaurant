<?php

namespace App\Http\Requests\Operation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashCloseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso lo valida el middleware "permission" de la ruta
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'integer', Rule::exists('tenant.cash_sessions', 'codmov')],
            'closing_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['cash_session_id' => 'sesión', 'closing_amount' => 'efectivo contado', 'notes' => 'observación'];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
