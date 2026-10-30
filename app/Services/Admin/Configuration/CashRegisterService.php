<?php

namespace App\Services\Admin\Configuration;

use App\Models\Tenant\CashRegister;
use App\Models\Tenant\CashSession;
use App\Models\Tenant\Printer;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Cajas registradoras de la empresa. Una caja puede tener una sola sesión abierta a la vez. */
class CashRegisterService extends BaseService
{
    protected string $model = CashRegister::class;

    protected string $label = 'Caja';

    protected array $searchable = ['code', 'name', 'detail', 'location'];

    protected array $sortable = ['codmov', 'code', 'name', 'state'];

    protected array $fields = ['code', 'name', 'detail', 'location', 'printer_id'];

    protected bool $audited = true;

    public function find(int $id): CashRegister
    {
        return CashRegister::query()->findOrFail($id);
    }

    public function printerOptions(): array
    {
        return Printer::query()->where('state', 1)->orderBy('name')->get(['codmov', 'code', 'name', 'purpose'])
            ->map(fn ($p) => ['id' => (int) $p->codmov, 'code' => $p->code, 'name' => $p->name, 'purpose' => $p->purpose])->all();
    }

    protected function query(): Builder
    {
        return parent::query()->with('printer:codmov,name');
    }

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        $paginator = $this->paginate($filters, $perPage);

        $open = CashSession::query()
            ->whereIn('cash_register_id', $paginator->getCollection()->pluck('codmov'))
            ->where('status', 'OPEN')->pluck('opened_by_name', 'cash_register_id');

        return $paginator->through(fn (CashRegister $register) => [
            'id' => (int) $register->codmov,
            'code' => $register->code,
            'name' => $register->name,
            'detail' => $register->detail,
            'location' => $register->location,
            'printer_id' => $register->printer_id ? (int) $register->printer_id : null,
            'printer' => $register->printer?->name,
            'state' => (int) $register->state,
            'open_by' => $open[$register->codmov] ?? null, // quién la tiene abierta ahora
        ]);
    }

    public function toggleState(Model $record): array
    {
        if ((int) $record->state === 1 && $this->hasOpenSession((int) $record->codmov)) {
            return ['data' => $record, 'flash' => $this->toast('La caja tiene una sesión abierta: ciérrala antes de inactivarla.', 'error')];
        }

        return parent::toggleState($record);
    }

    public function destroy(CashRegister $register): array
    {
        if (CashSession::query()->where('cash_register_id', $register->codmov)->exists()) {
            return ['data' => $register, 'flash' => $this->toast('La caja ya tiene sesiones registradas: no se puede eliminar, solo inactivar.', 'error')];
        }

        $register->delete();

        return ['data' => $register, 'flash' => $this->toast('Caja eliminada.')];
    }

    protected function hasOpenSession(int $registerId): bool
    {
        return CashSession::query()->where('cash_register_id', $registerId)->where('status', 'OPEN')->exists();
    }
}
