<?php

namespace App\Http\Requests\Third;

use App\Services\Third\ContactService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso lo valida el middleware "permission" de la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'customer' => $this->boolean('customer'),
            'supplier' => $this->boolean('supplier'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('contact') ? (int) $this->route('contact') : null;
        $types = array_column(ContactService::documentTypes(), 'code');

        return [
            // Con los tipos importados desde soporte se exige uno de ellos; si aún no se importaron, texto libre.
            'document_type' => ['required', 'string', 'max:10', $types ? Rule::in($types) : 'string'],
            'document_number' => ['required', 'string', 'max:30',
                Rule::unique('tenant.clients_suppliers', 'document_number')
                    ->where('document_type', $this->input('document_type'))
                    ->ignore($id, 'codmov')->whereNull('deleted_at')],
            'business_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required_without:business_name', 'nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'customer' => ['boolean', function (string $attribute, mixed $value, Closure $fail) {
                if (! $this->boolean('customer') && ! $this->boolean('supplier')) {
                    $fail('Marca si es cliente, proveedor o ambos.');
                }
            }],
            'supplier' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'document_type' => 'tipo de documento', 'document_number' => 'número de documento', 'business_name' => 'razón social',
            'first_name' => 'nombre', 'last_name' => 'apellido', 'email' => 'correo', 'phone' => 'teléfono',
            'mobile' => 'celular', 'address' => 'dirección',
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'Ya existe un tercero con ese documento.',
            'first_name.required_without' => 'Escribe el nombre o la razón social.',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
