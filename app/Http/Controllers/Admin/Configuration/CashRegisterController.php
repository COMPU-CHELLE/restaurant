<?php

namespace App\Http\Controllers\Admin\Configuration;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configuration\CashRegisterRequest;
use App\Services\Admin\Configuration\CashRegisterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashRegisterController extends Controller
{
    use CrudResponses;

    public function __construct(private CashRegisterService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('admin/configuracion/cajas', [
            'registers' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => ['printers' => $this->service->printerOptions()],
        ]);
    }

    public function store(CashRegisterRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    // Los modelos del tenant se cargan aquí por id (ya con la base de la empresa activa).
    public function update(CashRegisterRequest $request, int $register): RedirectResponse
    {
        return $this->done($this->service->update($this->service->find($register), $request->validated()));
    }

    public function toggle(int $register): RedirectResponse
    {
        return $this->done($this->service->toggleState($this->service->find($register)));
    }

    public function destroy(int $register): RedirectResponse
    {
        return $this->done($this->service->destroy($this->service->find($register)));
    }
}
