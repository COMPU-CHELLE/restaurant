<?php

namespace App\Services\EInvoice;

use App\Models\Support\Company;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auditoría de documentos electrónicos de todas las empresas, sin importar el proveedor ni el país.
 * Lee factus_documents_requests de cada tenant (la columna `provider` dice quién procesó cada documento).
 */
class EInvoiceAuditService
{
    /** Máximo de documentos que se traen por empresa para la lista (los contadores no tienen tope). */
    public const PER_TENANT_LIMIT = 200;

    /** Columnas livianas para la lista: payload y response (JSON grandes) se piden solo en el detalle. */
    private const LIST_COLUMNS = [
        'codmov', 'provider', 'document_type', 'document_table', 'document_id', 'reference_code',
        'document_number', 'cufe', 'status', 'error', 'environment', 'endpoint', 'http_code', 'created_at',
    ];

    /**
     * @param array{company?:?string,status?:?string,provider?:?string,country?:?string,search?:?string} $filters
     * @return array{rows:Collection,stats:array,companies:Collection}
     */
    public function collect(array $filters): array
    {
        // El filtro de la pantalla manda; si no hay, la empresa elegida en el selector de Soporte
        $companyFilter = ($filters['company'] ?? null) ?: (session()->has('company_id') ? session('company_code') : null);

        $companies = Company::with('country')
            ->whereNotNull('basdat')
            ->when($companyFilter, fn ($q, $code) => $q->where('code', $code))
            ->when($filters['country'] ?? null, fn ($q, $iso2) => $q->whereHas(
                'country', fn ($c) => $c->where('iso2', strtoupper($iso2))
            ))
            ->orderBy('empnom')
            ->get();

        $status = $filters['status'] ?? null;
        $status = ($status && $status !== 'all') ? $status : null;

        $rows = collect();
        $stats = ['emitidas' => 0, 'noEmitidas' => 0, 'por_proveedor' => []];

        foreach ($companies as $company) {
            try {
                app(TenantManager::class)->setTenantConnection($company->basdat);

                $query = DB::connection('tenant')->table('factus_documents_requests')
                    ->when($filters['provider'] ?? null, fn ($q, $p) => $q->where('provider', $p))
                    ->when($filters['search'] ?? null, function ($q, $s) {
                        $q->where(function ($w) use ($s) {
                            $w->where('document_number', 'like', "%{$s}%")
                                ->orWhere('document_type', 'like', "%{$s}%")
                                ->orWhere('reference_code', 'like', "%{$s}%")
                                ->orWhere('cufe', 'like', "%{$s}%");
                        });
                    });

                // Contadores: respetan empresa/proveedor/búsqueda pero NO el filtro de estado,
                // para que ambos totales sigan siendo informativos al filtrar por estado.
                foreach ((clone $query)->selectRaw('provider, status, COUNT(*) as total')
                    ->groupBy('provider', 'status')->get() as $c) {
                    $bucket = $c->provider ?: 'desconocido';
                    $stats['por_proveedor'][$bucket] ??= ['emitidas' => 0, 'noEmitidas' => 0];

                    if ($c->status === 'OK') {
                        $stats['emitidas'] += $c->total;
                        $stats['por_proveedor'][$bucket]['emitidas'] += $c->total;
                    } elseif ($c->status === 'error') {
                        $stats['noEmitidas'] += $c->total;
                        $stats['por_proveedor'][$bucket]['noEmitidas'] += $c->total;
                    }
                }

                $docs = (clone $query)
                    ->when($status, fn ($q, $s) => $q->where('status', $s))
                    ->orderByDesc('codmov')
                    ->limit(self::PER_TENANT_LIMIT)
                    ->get(self::LIST_COLUMNS);

                foreach ($docs as $doc) {
                    $rows->push([
                        'uid' => $company->code . '-' . $doc->codmov,
                        'empresa' => $company->empnom,
                        'codigo_empresa' => $company->code,
                        'pais' => strtoupper($company->country?->iso2 ?? ''),
                        ...((array) $doc),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("Auditoría e-invoice: error en tenant {$company->code}: " . $e->getMessage());
            }
        }

        // Orden global por fecha (codmov solo es comparable dentro de un mismo tenant)
        $rows = $rows->sortByDesc(fn ($r) => (string) ($r['created_at'] ?? ''))->values();

        return ['rows' => $rows, 'stats' => $stats, 'companies' => $companies];
    }

    /** Detalle completo de un documento (incluye payload y respuesta, con datos sensibles ocultos). */
    public function detail(string $companyCode, int $id): ?array
    {
        $company = Company::where('code', $companyCode)->whereNotNull('basdat')->first();

        if (! $company) {
            return null;
        }

        app(TenantManager::class)->setTenantConnection($company->basdat);

        $doc = DB::connection('tenant')->table('factus_documents_requests')->where('codmov', $id)->first();

        if (! $doc) {
            return null;
        }

        $doc = (array) $doc;
        foreach (['payload', 'response'] as $key) {
            $decoded = isset($doc[$key]) ? json_decode((string) $doc[$key], true) : null;
            $doc[$key] = is_array($decoded) ? $this->redact($decoded) : ($doc[$key] ?? null);
        }

        return $doc;
    }

    /** Oculta valores de llaves que parezcan credenciales. */
    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            } elseif (is_string($key) && preg_match('/pass|secret|token|authorization|pin|clave/i', $key)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
