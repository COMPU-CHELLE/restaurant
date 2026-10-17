<?php

namespace App\Http\Controllers;

use App\Services\Support\UserContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function select(Request $request, UserContextService $contexts): Response|RedirectResponse
    {
        $companies = $contexts->accessibleCompanies($request->user());

        // Una sola empresa y no es SOPORTE: no hay nada que elegir.
        if ($companies->count() === 1 && ! $contexts->supportRole($request->user())) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('company/select', [
            'companies' => $companies,
            'currentId' => session('company_id'),
        ]);
    }

    public function store(Request $request, UserContextService $contexts, int $company): RedirectResponse
    {
        $allowed = $contexts->accessibleCompanies($request->user())->contains('id', $company);

        abort_unless($allowed, 403, 'No tienes acceso a esa empresa.');

        session(['company_id' => $company]);

        if ($contexts->supportRole($request->user())) {
            session(['soporte_company_id' => $company]);
        }

        session()->forget(['menu', 'permissions', 'menu_key']);

        return redirect()->route('dashboard');
    }
}
