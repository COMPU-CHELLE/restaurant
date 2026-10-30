<?php

namespace App\Http\Requests\Operation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso lo valida el middleware "permission" de la ruta
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'integer', Rule::exists('tenant.cash_sessions', 'codmov')],
            'movement_type' => ['required', Rule::in(['INCOME', 'EXPENSE'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'concept' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['cash_session_id' => 'sesión', 'movement_type' => 'tipo', 'amount' => 'valor', 'concept' => 'concepto', 'detail' => 'detalle'];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'El valor debe ser mayor que cero.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
