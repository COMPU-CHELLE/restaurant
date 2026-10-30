<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Services\Audit\SoflogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Bitácora: solo SOPORTE. */
class SupportSoflogController extends Controller
{
    use CrudResponses;

    public function __construct(private SoflogService $service)
    {
    }

    public function index(Request $request): Response
    {
        $companyId = null;

        $filters = $this->tableFilters($request) + [
            'tipeve' => $request->input('tipeve') ?: null,
            'codmod' => $request->input('codmod') ?: null,
            'codemp' => $request->input('codemp') ?: null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];

        return Inertia::render('soporte/bitacora', [
            'entries' => $this->service->paginate($filters, $filters['per_page'], $companyId),
            'filters' => $filters,
            'options' => $this->service->options($companyId),
        ]);
    }
}
