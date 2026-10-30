<?php

namespace App\Services\Operation;

use App\Models\Tenant\CashMovement;
use App\Models\Tenant\CashRegister;
use App\Models\Tenant\CashSession;
use App\Models\Tenant\Payment;
use App\Models\Tenant\PaymentMethod;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Sesiones de caja: apertura, ingresos/egresos, cierre con arqueo.
 *
 * Efectivo esperado = base de apertura + cobros en efectivo + ingresos − egresos.
 * (Efectivo = métodos de pago con medio DIAN 10 o con código EFECTIVO.)
 * Reglas: una caja solo puede tener UNA sesión abierta (lo garantiza también la base con un UNIQUE);
 * un usuario solo puede tener UNA caja abierta; un egreso no puede superar el efectivo que hay en caja;
 * si el conteo no coincide con lo esperado, el cierre exige una observación.
 */
class CashSessionService extends BaseService
{
    protected string $model = CashSession::class;

    protected string $label = 'Sesión de caja';

    protected array $searchable = ['opened_by_name', 'notes'];

    protected array $sortable = ['codmov', 'opened_at', 'closed_at', 'status'];

    protected function query(): Builder
    {
        return parent::query()->with('register:codmov,code,name,location');
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (in_array($filters['status'] ?? null, ['OPEN', 'CLOSED'], true)) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['register'])) {
            $query->where('cash_register_id', (int) $filters['register']);
        }
    }

    // ------------------------------------------------------------------ consulta

    /** Una tarjeta por caja activa, con el resumen en vivo de su sesión abierta. */
    public function registers(): array
    {
        $registers = CashRegister::query()->where('state', 1)->orderBy('name')->get();

        $open = CashSession::query()->where('status', 'OPEN')
            ->whereIn('cash_register_id', $registers->pluck('codmov'))->get()->keyBy('cash_register_id');

        return $registers->map(function (CashRegister $register) use ($open) {
            $session = $open->get($register->codmov);

            return [
                'id' => (int) $register->codmov,
                'code' => $register->code,
                'name' => $register->name,
                'location' => $register->location,
                'session' => $session ? [
                    'id' => (int) $session->codmov,
                    'opened_by' => $session->opened_by_name,
                    'mine' => (int) $session->opened_by === (int) auth()->id(),
                    'opened_at' => $session->opened_at?->toIso8601String(),
                    'opening_amount' => (float) $session->opening_amount,
                ] + $this->summary($session) : null,
            ];
        })->all();
    }

    public function paginateSessions(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->paginate($filters + ['direction' => 'desc'], $perPage)->through(fn (CashSession $session) => [
            'id' => (int) $session->codmov,
            'register' => $session->register?->name,
            'opened_by' => $session->opened_by_name,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
            'opening_amount' => (float) $session->opening_amount,
            'expected_amount' => $session->status === 'CLOSED' ? (float) $session->expected_amount : null,
            'closing_amount' => $session->closing_amount !== null ? (float) $session->closing_amount : null,
            'difference_amount' => $session->difference_amount !== null ? (float) $session->difference_amount : null,
            'status' => $session->status,
        ]);
    }

    public function registerOptions(): array
    {
        return CashRegister::query()->orderBy('name')->get(['codmov', 'name'])
            ->map(fn ($r) => ['id' => (int) $r->codmov, 'name' => $r->name])->all();
    }

    /** Datos del arqueo de una sesión (abierta o cerrada). */
    public function show(int $id): array
    {
        $session = CashSession::query()->with('register:codmov,code,name,location')->findOrFail($id);
        $summary = $this->summary($session);

        $closedBy = $session->closed_by ? User::query()->whereKey($session->closed_by)->value('name') : null;
        $closed = $session->status === 'CLOSED';

        return [
            'session' => [
                'id' => (int) $session->codmov,
                'register' => $session->register?->name,
                'location' => $session->register?->location,
                'status' => $session->status,
                'opened_by' => $session->opened_by_name,
                'closed_by' => $closedBy,
                'opened_at' => $session->opened_at?->toIso8601String(),
                'closed_at' => $session->closed_at?->toIso8601String(),
                'opening_amount' => (float) $session->opening_amount,
                'expected_amount' => $closed ? (float) $session->expected_amount : $summary['expected'],
                'closing_amount' => $session->closing_amount !== null ? (float) $session->closing_amount : null,
                'difference_amount' => $session->difference_amount !== null ? (float) $session->difference_amount : null,
                'notes' => $session->notes,
            ],
            'summary' => $summary,
            'movements' => CashMovement::query()->where('cash_session_id', $session->codmov)->orderBy('codmov')->get()
                ->map(fn ($m) => [
                    'id' => (int) $m->codmov, 'type' => $m->movement_type, 'amount' => (float) $m->amount,
                    'concept' => $m->concept, 'detail' => $m->detail, 'by' => $m->created_by_name,
                    'at' => $m->created_at?->toIso8601String(),
                ])->all(),
        ];
    }

    /**
     * Totales de la sesión calculados desde los cobros y los movimientos.
     *
     * @return array{methods: array, total_sales: float, cash_payments: float, incomes: float, expenses: float, expected: float}
     */
    public function summary(CashSession $session): array
    {
        $cashIds = $this->cashMethodIds();

        $methods = Payment::query()
            ->where('cash_session_id', $session->codmov)
            ->with('paymentMethod:codmov,code,name')
            ->get()
            ->groupBy('payment_method_id')
            ->map(function ($group, $methodId) use ($cashIds) {
                $method = $group->first()->paymentMethod;

                return [
                    'name' => $method?->name ?? 'Método eliminado',
                    'count' => $group->count(),
                    'amount' => round((float) $group->sum('amount'), 2),
                    'surcharge' => round((float) $group->sum('surcharge_amount'), 2),
                    'total' => round((float) $group->sum('total_charged'), 2),
                    'cash' => in_array((int) $methodId, $cashIds, true),
                ];
            })->values()->all();

        $cashPayments = round((float) collect($methods)->where('cash', true)->sum('total'), 2);
        $totalSales = round((float) collect($methods)->sum('total'), 2);

        $moved = CashMovement::query()->where('cash_session_id', $session->codmov)
            ->selectRaw('movement_type, sum(amount) as total')->groupBy('movement_type')->pluck('total', 'movement_type');

        $incomes = round((float) ($moved['INCOME'] ?? 0), 2);
        $expenses = round((float) ($moved['EXPENSE'] ?? 0), 2);

        return [
            'methods' => $methods,
            'total_sales' => $totalSales,
            'cash_payments' => $cashPayments,
            'incomes' => $incomes,
            'expenses' => $expenses,
            'expected' => round((float) $session->opening_amount + $cashPayments + $incomes - $expenses, 2),
        ];
    }

    // ------------------------------------------------------------------ acciones

    public function open(array $data): array
    {
        $register = CashRegister::query()->where('state', 1)->findOrFail((int) $data['cash_register_id']);
        $user = auth()->user();

        if (CashSession::query()->where('opened_by', $user->id)->where('status', 'OPEN')->exists()) {
            return $this->failure('Ya tienes una caja abierta. Ciérrala antes de abrir otra.');
        }

        try {
            $this->transaction(fn () => CashSession::query()->create([
                'cash_register_id' => $register->codmov,
                'opened_by' => $user->id,
                'opened_by_name' => $user->name,
                'opening_amount' => $data['opening_amount'],
                'expected_amount' => $data['opening_amount'],
                'opened_at' => now(),
                'status' => 'OPEN',
                'open_register_id' => $register->codmov, // UNIQUE: una sola sesión abierta por caja
                'notes' => $data['notes'] ?? null,
            ]));
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return $this->failure('Esa caja ya está abierta por otro usuario.');
            }

            throw $e;
        }

        return ['data' => $register, 'flash' => $this->toast("Caja «{$register->name}» abierta.")];
    }

    public function addMovement(array $data): array
    {
        return $this->transaction(function () use ($data) {
            $session = CashSession::query()->lockForUpdate()->findOrFail((int) $data['cash_session_id']);

            if ($session->status !== 'OPEN') {
                return $this->failure('La sesión de caja ya está cerrada.');
            }

            $amount = round((float) $data['amount'], 2);
            $isExpense = $data['movement_type'] === 'EXPENSE';

            if ($isExpense && $amount > $this->summary($session)['expected'] + 0.001) {
                return $this->failure('No hay suficiente efectivo en caja para ese egreso.');
            }

            CashMovement::query()->create([
                'cash_session_id' => $session->codmov,
                'movement_type' => $data['movement_type'],
                'amount' => $amount,
                'concept' => $data['concept'],
                'detail' => $data['detail'] ?? null,
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name,
            ]);

            return ['data' => $session, 'flash' => $this->toast($isExpense ? 'Egreso registrado.' : 'Ingreso registrado.')];
        });
    }

    public function close(array $data): array
    {
        return $this->transaction(function () use ($data) {
            $session = CashSession::query()->lockForUpdate()->findOrFail((int) $data['cash_session_id']);

            if ($session->status !== 'OPEN') {
                return $this->failure('La sesión de caja ya está cerrada.');
            }

            $expected = $this->summary($session)['expected'];
            $counted = round((float) $data['closing_amount'], 2);
            $difference = round($counted - $expected, 2);
            $note = trim((string) ($data['notes'] ?? ''));

            if ($difference != 0.0 && $note === '') {
                throw ValidationException::withMessages([
                    'notes' => 'El conteo no coincide con lo esperado. Escribe una observación.',
                ]);
            }

            $session->forceFill([
                'expected_amount' => $expected,
                'closing_amount' => $counted,
                'difference_amount' => $difference,
                'closed_at' => now(),
                'closed_by' => auth()->id(),
                'status' => 'CLOSED',
                'open_register_id' => null, // libera la caja para otra apertura
                'notes' => trim(($session->notes ? $session->notes . "\n" : '') . $note) ?: null,
            ])->save();

            $text = $difference == 0.0 ? 'sin diferencia' : ($difference > 0 ? 'sobrante' : 'faltante');

            return ['data' => $session, 'flash' => $this->toast("Caja cerrada ({$text}).", $difference == 0.0 ? 'success' : 'warning')];
        });
    }

    // ------------------------------------------------------------------ auxiliares

    /** @return array<int, int> ids de los métodos de pago que entran al cajón (efectivo) */
    protected function cashMethodIds(): array
    {
        $hasMeans = Schema::connection('tenant')->hasColumn('payment_methods', 'payment_means_code');

        return PaymentMethod::query()
            ->where(function (Builder $q) use ($hasMeans) {
                $q->where('code', 'EFECTIVO');

                if ($hasMeans) {
                    $q->orWhere('payment_means_code', '10');
                }
            })
            ->pluck('codmov')->map(fn ($id) => (int) $id)->all();
    }

    protected function failure(string $message): array
    {
        return ['data' => null, 'flash' => $this->toast($message, 'error')];
    }
}
