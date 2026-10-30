<?php

namespace App\Http\Requests\Admin\Configuration;

use App\Services\Admin\Configuration\PrinterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrinterRequest extends FormRequest
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
        $id = $this->route('printer') ? (int) $this->route('printer') : null;

        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('tenant.printers', 'code')->ignore($id, 'codmov')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:255'],
            'printer_type' => ['required', Rule::in(array_keys(PrinterService::TYPES))],
            'connection_type' => ['required', Rule::in(array_keys(PrinterService::CONNECTIONS))],
            'ip_address' => ['required_if:connection_type,NETWORK', 'nullable', 'ip'],
            'port' => ['required_if:connection_type,NETWORK', 'nullable', 'integer', 'between:1,65535'],
            'paper_width' => ['required', Rule::in(array_keys(PrinterService::PAPER))],
            'purpose' => ['required', Rule::in(array_keys(PrinterService::PURPOSES))],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'código', 'name' => 'nombre', 'detail' => 'descripción', 'printer_type' => 'tipo', 'connection_type' => 'conexión', 'ip_address' => 'dirección IP', 'port' => 'puerto', 'paper_width' => 'papel', 'purpose' => 'uso'];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite mayúsculas, números, guion y guion bajo.',
            'code.unique' => 'Ya existe un registro con ese código.',
            'ip_address.required_if' => 'Escribe la IP de la impresora de red.',
            'ip_address.ip' => 'La dirección IP no es válida.',
            'port.required_if' => 'Escribe el puerto (normalmente 9100).',
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }
}
