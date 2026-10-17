<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\CompanyRequest;
use App\Models\Support\Company;
use App\Services\Support\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportCompanyController extends Controller
{
    use CrudResponses;

    public function __construct(private CompanyService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('soporte/empresas', [
            'companies' => $this->service
                ->paginate($filters, $filters['per_page'])
                ->through(fn (Company $company) => $this->service->toRow($company)),
            'filters' => $filters,
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        return $this->done($this->service->update($company, $request->validated()));
    }

    public function toggle(Company $company): RedirectResponse
    {
        return $this->done($this->service->toggleState($company));
    }

    public function provision(Request $request, Company $company): RedirectResponse
    {
        return $this->done($this->service->provision($company, $request->boolean('adopt')));
    }
}
