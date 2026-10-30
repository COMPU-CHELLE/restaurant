<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\FactusPayRequest;
use App\Models\Support\Company;
use App\Models\Support\FactusPayService;
use App\Services\Support\FactusPayConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportFactusPayController extends Controller
{
    use CrudResponses;

    public function __construct(private FactusPayConfigService $service) {}

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('soporte/factus-pay', [
            'configs' => $this->service->paginate($filters, $filters['per_page'])
                ->through(fn (FactusPayService $c) => $this->service->toRow($c)),
            'filters' => $filters,
            'options' => [
                'companies' => Company::query()->orderBy('empnom')->get(['codmov', 'code', 'empnom'])
                    ->map(fn ($c) => ['id' => (int) $c->codmov, 'code' => $c->code, 'name' => $c->empnom])->all(),
                'hosts' => config('factus.pay_defaults'),
            ],
        ]);
    }

    public function store(FactusPayRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    public function update(FactusPayRequest $request, FactusPayService $pay): RedirectResponse
    {
        return $this->done($this->service->update($pay, $request->validated()));
    }

    public function toggle(FactusPayService $pay): RedirectResponse
    {
        return $this->done($this->service->toggleState($pay));
    }

    /** Prueba con los datos del formulario (y los secretos guardados si van en blanco). */
    public function test(FactusPayRequest $request): RedirectResponse
    {
        $stored = $request->filled('id') ? FactusPayService::find($request->integer('id')) : null;

        return $this->report($this->service->testConnection($request->validated(), $stored));
    }

    public function testStored(FactusPayService $pay): RedirectResponse
    {
        return $this->report($this->service->testConnection([], $pay));
    }

    private function report(array $result): RedirectResponse
    {
        return $this->done(['flash' => $result['success']
            ? ['toast' => 'Conexión exitosa con Factus Pay.', 'type' => 'success']
            : ['toast' => $result['error'] ?? 'No se pudo conectar con Factus Pay.', 'type' => 'error']]);
    }
}
