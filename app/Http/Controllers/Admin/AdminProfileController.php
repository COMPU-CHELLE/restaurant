<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileRequest;
use App\Services\Admin\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminProfileController extends Controller
{
    use CrudResponses;

    public function __construct(private ProfileService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request);

        return Inertia::render('admin/perfiles', [
            'profiles' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'catalog' => $this->service->catalog(),
        ]);
    }

    public function store(ProfileRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    // Los modelos del tenant se cargan aquí (ya con la base de la empresa activa), no por binding implícito.
    public function update(ProfileRequest $request, int $profile): RedirectResponse
    {
        return $this->done($this->service->update($this->service->find($profile), $request->validated()));
    }

    public function toggle(int $profile): RedirectResponse
    {
        return $this->done($this->service->toggleState($this->service->find($profile)));
    }

    public function destroy(int $profile): RedirectResponse
    {
        return $this->done($this->service->destroy($this->service->find($profile)));
    }
}
