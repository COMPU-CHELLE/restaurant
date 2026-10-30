<?php

namespace App\Http\Controllers\Third;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Third\ContactRequest;
use App\Services\Third\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    use CrudResponses;

    public function __construct(private ContactService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $this->tableFilters($request) + [
            'kind' => in_array($request->input('kind'), ['customer', 'supplier'], true) ? $request->input('kind') : null,
        ];

        return Inertia::render('terceros/contactos', [
            'contacts' => $this->service->paginateRows($filters, $filters['per_page']),
            'filters' => $filters,
            'options' => ['documentTypes' => ContactService::documentTypes()],
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        return $this->done($this->service->store($request->validated()));
    }

    public function update(ContactRequest $request, int $contact): RedirectResponse
    {
        return $this->done($this->service->update($this->service->find($contact), $request->validated()));
    }

    public function toggle(int $contact): RedirectResponse
    {
        return $this->done($this->service->toggleState($this->service->find($contact)));
    }

    public function destroy(int $contact): RedirectResponse
    {
        return $this->done($this->service->destroy($this->service->find($contact)));
    }
}
