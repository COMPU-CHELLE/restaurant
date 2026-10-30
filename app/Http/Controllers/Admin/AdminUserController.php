<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyUserRequest;
use App\Models\Support\Role;
use App\Models\Tenant\Profile;
use App\Models\User;
use App\Services\Admin\CompanyUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    use CrudResponses;

    public function __construct(private CompanyUserService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('admin/usuarios', [
            'users' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => [
                'roles' => Role::query()->whereIn('code', ['ADMIN', 'USER'])->orderBy('code')->get(['codmov', 'code', 'name'])
                    ->map(fn ($r) => ['id' => (int) $r->codmov, 'code' => $r->code, 'name' => $r->name])->all(),
                'profiles' => Profile::query()->where('state', 1)->orderBy('name')->get(['codmov', 'code', 'name'])
                    ->map(fn ($p) => ['id' => (int) $p->codmov, 'code' => $p->code, 'name' => $p->name])->all(),
            ],
        ]);
    }

    public function store(CompanyUserRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    public function update(CompanyUserRequest $request, User $user): RedirectResponse
    {
        return $this->done($this->service->update($user, $request->validated()));
    }

    public function toggle(User $user): RedirectResponse
    {
        return $this->done($this->service->toggleState($user));
    }

    public function destroy(User $user): RedirectResponse
    {
        return $this->done($this->service->detach($user));
    }
}
