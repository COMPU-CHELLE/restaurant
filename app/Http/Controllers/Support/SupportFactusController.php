<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\FactusRequest;
use App\Models\Support\Company;
use App\Models\Support\FactusService;
use App\Services\Support\FactusConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportFactusController extends Controller
{
    use CrudResponses;

    public function __construct(private FactusConfigService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('soporte/factus', [
            'configs' => $this->service
                ->paginate($filters, $filters['per_page'])
                ->through(fn (FactusService $config) => $this->service->toRow($config)),
            'filters' => $filters,
            'options' => [
                'companies' => Company::query()->orderBy('empnom')->get(['codmov', 'code', 'empnom'])
                    ->map(fn ($c) => ['id' => (int) $c->codmov, 'code' => $c->code, 'name' => $c->empnom])->all(),
                'defaults' => config('factus.defaults'),
            ],
        ]);
    }

    public function store(FactusRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    public function update(FactusRequest $request, FactusService $factus): RedirectResponse
    {
        return $this->done($this->service->update($factus, $request->validated()));
    }

    public function toggle(FactusService $factus): RedirectResponse
    {
        return $this->done($this->service->toggleState($factus));
    }

    /** Prueba con los datos del formulario (y, si trae id, con los secretos guardados). */
    public function test(FactusRequest $request): RedirectResponse
    {
        $stored = $request->filled('id') ? FactusService::find($request->integer('id')) : null;

        return $this->reportTest($this->service->testConnection($request->validated(), $stored));
    }

    /** Prueba con lo guardado (acción de la fila). */
    public function testStored(FactusService $factus): RedirectResponse
    {
        return $this->reportTest($this->service->testConnection([], $factus));
    }

    private function reportTest(array $result): RedirectResponse
    {
        return $this->done(['flash' => $result['success']
            ? ['toast' => 'Conexión exitosa con Factus. El token dura ' . ($result['expires_in'] ?? 0) . ' segundos.', 'type' => 'success']
            : ['toast' => $result['error'] ?? 'No se pudo conectar con Factus.', 'type' => 'error']]);
    }
}
