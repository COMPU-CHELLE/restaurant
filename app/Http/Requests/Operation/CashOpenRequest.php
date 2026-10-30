<?php

namespace App\Http\Requests\Operation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashOpenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso lo valida el middleware "permission" de la ruta
    }

    public function rules(): array
    {
        return [
            'cash_register_id' => ['required', 'integer', Rule::exists('tenant.cash_registers', 'codmov')->where('state', 1)->whereNull('deleted_at')],
            'opening_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['cash_register_id' => 'caja', 'opening_amount' => 'base de apertura', 'notes' => 'observación'];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
