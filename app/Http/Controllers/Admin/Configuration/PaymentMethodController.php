<?php

namespace App\Http\Controllers\Admin\Configuration;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configuration\PaymentMethodRequest;
use App\Services\Admin\Configuration\PaymentMethodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    use CrudResponses;

    public function __construct(private PaymentMethodService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('admin/configuracion/metodos-pago', [
            'methods' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => \App\Services\Admin\Configuration\PaymentMethodService::dianOptions(),
        ]);
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    // Los modelos del tenant se cargan aquí por id (ya con la base de la empresa activa).
    public function update(PaymentMethodRequest $request, int $method): RedirectResponse
    {
        return $this->done($this->service->update($this->service->find($method), $request->validated()));
    }

    public function toggle(int $method): RedirectResponse
    {
        return $this->done($this->service->toggleState($this->service->find($method)));
    }

    public function destroy(int $method): RedirectResponse
    {
        return $this->done($this->service->destroy($this->service->find($method)));
    }
}
