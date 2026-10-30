<?php

namespace App\Http\Controllers;

use App\Models\Support\Company;
use App\Models\Support\FactusService;
use App\Models\Support\Soflog;
use App\Models\Support\UserCompanyContext;
use App\Models\Tenant\CatalogRecord;
use App\Models\Tenant\Profile;
use App\Models\User;
use App\Services\Audit\SoflogService;
use App\Services\Tenant\TenantManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/** Un solo /dashboard: soporte ve datos por empresa, el administrador sus estadísticas y el usuario su mapa. */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return match (strtoupper((string) session('role_code'))) {
            'SOPORTE' => $this->support($request),
            'ADMIN' => $this->admin(),
            default => Inertia::render('dashboard'),
        };
    }

    private function support(Request $request): Response
    {
        $companies = Company::query()->orderBy('empnom')->get();

        $users = UserCompanyContext::query()->selectRaw('company_id, count(*) as total')->groupBy('company_id')->pluck('total', 'company_id');
        $factus = FactusService::query()->where('active', true)->get()->groupBy('codemp');
        $last = Soflog::query()->whereNotNull('codemp')->selectRaw('codemp, max(created_at) as last_at')->groupBy('codemp')->pluck('last_at', 'codemp');

        $rows = $companies->map(function (Company $company) use ($users, $factus, $last) {
            $config = $factus->get($company->codmov)?->first();

            return [
                'id' => (int) $company->codmov,
                'code' => $company->code,
                'name' => $company->empnom,
                'state' => (int) $company->state,
                'provision_status' => $company->provision_status,
                'users' => (int) ($users[$company->codmov] ?? 0),
                'factus' => $config ? ['environment' => $config->environment, 'token' => $config->token ? ($config->tokenExpired() ? 'expired' : 'valid') : 'none'] : null,
                'last_activity' => $last[$company->codmov] ?? null,
            ];
        })->values()->all();

        $selectedId = $request->integer('company') ?: (int) session('company_id');
        $selected = $this->companyDetail($companies->firstWhere('codmov', $selectedId));

        return Inertia::render('soporte/dashboard', [
            'stats' => [
                'companies' => [
                    'total' => $companies->count(),
                    'active' => $companies->where('state', 1)->count(),
                    'ready' => $companies->where('provision_status', 'ready')->count(),
                    'attention' => $companies->whereIn('provision_status', ['failed', 'pending'])->count(),
                ],
                'users' => ['total' => User::query()->count(), 'active' => User::query()->where('state', 1)->count()],
                'factus' => ['active' => $factus->flatten()->count()],
                'events24h' => Soflog::query()->where('created_at', '>=', now()->subDay())->count(),
            ],
            'companies' => $rows,
            'selected' => $selected,
        ]);
    }

    /** Datos de una empresa; lo del tenant se lee cambiando la conexión a su base. */
    private function companyDetail(?Company $company): ?array
    {
        if (! $company) {
            return null;
        }

        $byRole = UserCompanyContext::query()->with('role')->where('company_id', $company->codmov)->get()
            ->groupBy(fn ($context) => $context->role?->code)->map->count();

        $profiles = $items = null;

        try {
            if ($company->provision_status === 'ready' && $company->basdat) {
                app(TenantManager::class)->setTenantConnection($company->basdat);
                $profiles = Profile::query()->count();
                $items = CatalogRecord::totalRows();
            }
        } catch (Throwable) {
            // base no disponible: se muestran solo los datos globales
        }

        return [
            'id' => (int) $company->codmov,
            'code' => $company->code,
            'name' => $company->empnom,
            'nit' => trim($company->empnit . ($company->empdig ? '-' . $company->empdig : '')),
            'database' => $company->basdat,
            'admins' => (int) ($byRole['ADMIN'] ?? 0),
            'operators' => (int) ($byRole['USER'] ?? 0),
            'profiles' => $profiles,
            'catalog_items' => $items,
            'recent' => Soflog::query()->where('codemp', $company->codmov)->orderByDesc('codmov')->limit(8)
                ->get(['tipeve', 'detail', 'codope', 'created_at'])
                ->map(fn ($log) => ['type' => $log->tipeve, 'detail' => $log->detail, 'user' => $log->codope, 'at' => $log->created_at?->toIso8601String()])
                ->all(),
        ];
    }

    private function admin(): Response
    {
        $companyId = (int) session('company_id');

        $byRole = UserCompanyContext::query()->with('role')->where('company_id', $companyId)->get()
            ->groupBy(fn ($context) => $context->role?->code)->map->count();

        $recent = app(SoflogService::class)->paginate([], 8, $companyId)->items();

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'admins' => (int) ($byRole['ADMIN'] ?? 0),
                'operators' => (int) ($byRole['USER'] ?? 0),
                'profiles' => Profile::query()->count(),
                'profilesActive' => Profile::query()->where('state', 1)->count(),
                'parameters' => CatalogRecord::totalRows(),
            ],
            'recent' => array_map(fn ($row) => [
                'type' => $row['tipeve'], 'detail' => $row['detail'], 'user' => $row['codope'], 'at' => $row['created_at'],
            ], $recent),
        ]);
    }
}
