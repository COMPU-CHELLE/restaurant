<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\CrudResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanySettingsRequest;
use App\Services\Admin\CompanySettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminCompanyController extends Controller
{
    use CrudResponses;

    public function __construct(private CompanySettingsService $service)
    {
    }

    public function show(): Response
    {
        return Inertia::render('admin/empresa', [
            'company' => $this->service->toArray($this->service->current()),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(CompanySettingsRequest $request): RedirectResponse
    {
        return $this->done($this->service->update(
            $this->service->current(),
            $request->validated(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        ));
    }
}
