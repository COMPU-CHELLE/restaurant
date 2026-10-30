<?php

namespace App\Services\Admin\Configuration;

use App\Models\Tenant\Payment;
use App\Models\Tenant\PaymentMethod;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Métodos de pago que acepta el restaurante (efectivo, tarjeta, transferencia…), con su recargo opcional y
 * los códigos DIAN (medio y forma de pago) que se usan al emitir la factura electrónica.
 */
class PaymentMethodService extends BaseService
{
    protected string $model = PaymentMethod::class;

    protected string $label = 'Método de pago';

    protected array $searchable = ['code', 'name', 'detail'];

    protected array $sortable = ['codmov', 'code', 'name', 'surcharge_percentage', 'state'];

    protected array $fields = ['code', 'name', 'detail', 'surcharge_percentage', 'payment_means_code', 'payment_form_code'];

    protected bool $audited = true;

    public function find(int $id): PaymentMethod
    {
        return PaymentMethod::query()->findOrFail($id);
    }

    /** Medios (10 efectivo, 48 tarjeta crédito…) y formas (1 contado, 2 crédito) importados desde soporte. */
    public static function dianOptions(): array
    {
        $read = function (string $table): array {
            try {
                return DB::connection('tenant')->table($table)->where('state', 1)->orderBy('code')->get(['code', 'name'])
                    ->map(fn ($row) => ['code' => (string) $row->code, 'name' => $row->name])->all();
            } catch (Throwable) {
                return []; // la tabla aún no existe o no se importó
            }
        };

        return ['means' => $read('payment_means'), 'forms' => $read('payment_forms')];
    }

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->paginate($filters, $perPage)->through(fn (PaymentMethod $method) => [
            'id' => (int) $method->codmov,
            'code' => $method->code,
            'name' => $method->name,
            'detail' => $method->detail,
            'surcharge_percentage' => (float) $method->surcharge_percentage,
            'payment_means_code' => $method->payment_means_code,
            'payment_form_code' => $method->payment_form_code,
            'state' => (int) $method->state,
        ]);
    }

    public function destroy(PaymentMethod $method): array
    {
        if (Payment::query()->where('payment_method_id', $method->codmov)->exists()) {
            return ['data' => $method, 'flash' => $this->toast('Ya hay pagos con este método: no se puede eliminar, solo inactivar.', 'error')];
        }

        $method->delete();

        return ['data' => $method, 'flash' => $this->toast('Método de pago eliminado.')];
    }
}
