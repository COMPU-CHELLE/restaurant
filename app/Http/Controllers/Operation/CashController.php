<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operation\CashCloseRequest;
use App\Http\Requests\Operation\CashMovementRequest;
use App\Http\Requests\Operation\CashOpenRequest;
use App\Models\Tenant\Companie;
use App\Services\Operation\CashSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashController extends Controller
{
    use CrudResponses;

    public function __construct(private CashSessionService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request) + [
            'status' => in_array($request->input('status'), ['OPEN', 'CLOSED'], true) ? $request->input('status') : null,
            'register' => $request->integer('register') ?: null,
        ];

        return Inertia::render('operacion/caja', [
            'registers' => $this->service->registers(),
            'sessions' => $this->service->paginateSessions($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => [
                'registers' => $this->service->registerOptions(),
                'currency' => Companie::query()->value('currency') ?: 'COP',
            ],
        ]);
    }

    public function show(int $session): Response
    {
        return Inertia::render('operacion/caja-arqueo', $this->service->show($session) + [
            'currency' => Companie::query()->value('currency') ?: 'COP',
        ]);
    }

    public function open(CashOpenRequest $request): RedirectResponse
    {
        return $this->done($this->service->open($request->validated()));
    }

    public function movement(CashMovementRequest $request): RedirectResponse
    {
        return $this->done($this->service->addMovement($request->validated()));
    }

    public function close(CashCloseRequest $request): RedirectResponse
    {
        return $this->done($this->service->close($request->validated()));
    }
}
