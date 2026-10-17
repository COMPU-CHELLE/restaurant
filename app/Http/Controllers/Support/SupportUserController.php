<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\UserRequest;
use App\Models\Support\Company;
use App\Models\Support\Role;
use App\Models\User;
use App\Services\Support\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportUserController extends Controller
{
    use CrudResponses;

    public function __construct(private UserService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('soporte/usuarios', [
            'users' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => [
                'companies' => Company::query()->orderBy('empnom')->get(['codmov', 'code', 'empnom'])
                    ->map(fn ($c) => ['id' => (int) $c->codmov, 'code' => $c->code, 'name' => $c->empnom])->all(),
                'roles' => Role::query()->whereIn('code', ['ADMIN', 'USER'])->orderBy('code')->get(['codmov', 'code', 'name'])
                    ->map(fn ($r) => ['id' => (int) $r->codmov, 'code' => $r->code, 'name' => $r->name])->all(),
            ],
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        return $this->done($this->service->update($user, $request->validated()));
    }

    public function toggle(User $user): RedirectResponse
    {
        return $this->done($this->service->toggleState($user));
    }
}
