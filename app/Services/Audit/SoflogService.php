<?php

namespace App\Services\Audit;

use App\Models\Support\Company;
use App\Models\Support\Soflog;
use App\Models\Support\UserCompanyContext;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SoflogService
{
    /**
     * @param  int|null  $companyId  null = bitácora global (soporte); con empresa = solo lo de esa empresa
     *                               (sus eventos + inicios de sesión de sus usuarios)
     */
    public function paginate(array $filters, int $perPage, ?int $companyId = null): LengthAwarePaginator
    {
        $query = $this->query($companyId);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(fn (Builder $q) => $q->where('detail', 'like', $like)->orWhere('codope', 'like', $like));
        }

        foreach (['tipeve', 'codmod'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if (! empty($filters['codemp']) && $companyId === null) {
            $query->where('codemp', (int) $filters['codemp']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('fecope', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('fecope', '<=', $filters['to']);
        }

        return $query->with('company:codmov,code,empnom')
            ->orderByDesc('codmov')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Soflog $log) => [
                'id' => (int) $log->codmov,
                'tipeve' => $log->tipeve,
                'detail' => $log->detail,
                'codmod' => $log->codmod,
                'codope' => $log->codope,
                'host' => $log->host,
                'basdat' => $log->basdat,
                'company' => $log->company ? ['code' => $log->company->code, 'name' => $log->company->empnom] : null,
                'created_at' => $log->created_at?->toIso8601String(),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ]);
    }

    /** Listas para los filtros. */
    public function options(?int $companyId = null): array
    {
        $base = $this->query($companyId);

        return [
            'types' => (clone $base)->distinct()->orderBy('tipeve')->pluck('tipeve')->all(),
            'modules' => (clone $base)->distinct()->orderBy('codmod')->pluck('codmod')->all(),
            'companies' => $companyId === null
                ? Company::query()->orderBy('empnom')->get(['codmov', 'code', 'empnom'])
                    ->map(fn ($c) => ['id' => (int) $c->codmov, 'code' => $c->code, 'name' => $c->empnom])->all()
                : [],
        ];
    }

    protected function query(?int $companyId): Builder
    {
        $query = Soflog::query();

        if ($companyId !== null) {
            $codes = User::query()
                ->whereIn('id', UserCompanyContext::query()->where('company_id', $companyId)->select('user_id'))
                ->pluck('code');

            $query->where(function (Builder $q) use ($companyId, $codes) {
                $q->where('codemp', $companyId)
                    ->orWhere(fn (Builder $auth) => $auth->where('codmod', 'AUTH')->whereIn('codope', $codes));
            });
        }

        return $query;
    }
}
