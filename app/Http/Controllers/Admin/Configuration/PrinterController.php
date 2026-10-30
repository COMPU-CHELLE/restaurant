<?php

namespace App\Http\Controllers\Admin\Configuration;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configuration\PrinterRequest;
use App\Services\Admin\Configuration\PrinterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrinterController extends Controller
{
    use CrudResponses;

    public function __construct(private PrinterService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('admin/impresiones/impresoras', [
            'printers' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => \App\Services\Admin\Configuration\PrinterService::options(),
        ]);
    }

    public function store(PrinterRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    // Los modelos del tenant se cargan aquí por id (ya con la base de la empresa activa).
    public function update(PrinterRequest $request, int $printer): RedirectResponse
    {
        return $this->done($this->service->update($this->service->find($printer), $request->validated()));
    }

    public function toggle(int $printer): RedirectResponse
    {
        return $this->done($this->service->toggleState($this->service->find($printer)));
    }

    public function destroy(int $printer): RedirectResponse
    {
        return $this->done($this->service->destroy($this->service->find($printer)));
    }
}
