<?php

namespace App\Http\Requests\Admin\Configuration;

use App\Services\Admin\Configuration\PaymentMethodService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
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
        $id = $this->route('method') ? (int) $this->route('method') : null;
        $dian = PaymentMethodService::dianOptions();

        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('tenant.payment_methods', 'code')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
            'surcharge_percentage' => ['required', 'numeric', 'between:0,100'],
            // Con los parámetros DIAN importados se exige un código de la lista; si no, texto libre.
            'payment_means_code' => ['nullable', 'string', 'max:10', $dian['means'] ? Rule::in(array_column($dian['means'], 'code')) : 'string'],
            'payment_form_code' => ['required', 'string', 'max:10', $dian['forms'] ? Rule::in(array_column($dian['forms'], 'code')) : 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código', 'name' => 'nombre', 'detail' => 'descripción', 'surcharge_percentage' => 'recargo',
            'payment_means_code' => 'medio de pago DIAN', 'payment_form_code' => 'forma de pago DIAN',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite mayúsculas, números, guion y guion bajo.',
            'code.unique' => 'Ya existe un método de pago con ese código.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
