<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Models\Tenant\FactusPayCollection;
use App\Services\Factus\FactusPayCollectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/** Listado de recaudos de Factus Pay de la empresa activa. */
class AdminCollectionController extends Controller
{
    use CrudResponses;

    private const STATUSES = ['pending', 'paid', 'expired', 'failed', 'cancelled'];

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request) + [
            'status' => in_array($request->input('status'), self::STATUSES, true) ? $request->input('status') : null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];

        $sortable = ['codmov', 'reference_code', 'amount', 'status', 'created_at', 'paid_at'];
        $sort = in_array($filters['sort'], $sortable, true) ? $filters['sort'] : 'codmov';

        $rows = FactusPayCollection::query()
            ->with('order:codmov,code')
            ->when($filters['search'], function (Builder $q, string $s) {
                $like = '%' . addcslashes($s, '%_\\') . '%';
                $q->where(fn (Builder $w) => $w->where('reference_code', 'like', $like)
                    ->orWhere('factus_collection_id', 'like', $like)
                    ->orWhereHas('order', fn (Builder $o) => $o->where('code', 'like', $like)));
            })
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($filters['from'], fn (Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'], fn (Builder $q, string $d) => $q->whereDate('created_at', '<=', $d))
            ->orderBy($sort, $filters['direction'])
            ->paginate($filters['per_page'])->withQueryString()
            ->through(fn (FactusPayCollection $c) => [
                'id' => (int) $c->codmov,
                'reference' => $c->reference_code,
                'order_id' => (int) $c->order_id,
                'order_code' => $c->order?->code,
                'amount' => (float) $c->amount,
                'method' => $c->method,
                'status' => $c->status,
                'payment_id' => $c->payment_id,
                'final' => $c->isFinal(),
                'created_at' => $c->created_at?->toIso8601String(),
                'paid_at' => $c->paid_at?->toIso8601String(),
                'expires_at' => $c->expires_at?->toIso8601String(),
                'last_checked_at' => $c->last_checked_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/recaudos', [
            'collections' => $rows,
            'filters' => $filters,
            'totals' => [
                'paid' => (float) FactusPayCollection::query()->where('status', 'paid')->sum('amount'),
                'pending' => (int) FactusPayCollection::query()->where('status', 'pending')->count(),
            ],
        ]);
    }

    /** Consulta el estado en Factus. No registra el pago en caja. */
    public function refresh(Request $request, int $collection, FactusPayCollectionService $service): RedirectResponse
    {
        try {
            $record = FactusPayCollection::query()->findOrFail($collection);
            $record = $service->refresh($record, (int) $request->session()->get('company_id'));

            return $this->done(['flash' => ['toast' => 'Estado actualizado: ' . $record->status . '.', 'type' => 'success']]);
        } catch (Throwable $e) {
            report($e);

            return $this->done(['flash' => ['toast' => $e->getMessage(), 'type' => 'error']]);
        }
    }
}
