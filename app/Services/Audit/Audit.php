<?php

namespace App\Services\Audit;

use App\Models\Support\Soflog;
use Illuminate\Support\Facades\Auth;

/** Punto único para escribir en la bitácora (lo usan el observador, el listener y las acciones manuales). */
class Audit
{
    public static function record(
        string $tipeve,
        string $codmod,
        string $detail,
        ?array $old = null,
        ?array $new = null,
        ?string $basdat = null,
        ?string $operator = null,
        ?int $companyId = null,
    ): void {
        $operator ??= Auth::user()?->code;

        if ($operator === null) {
            return; // sin usuario (seeders, colas): no se audita
        }

        $now = now();

        Soflog::query()->create([
            'tipeve' => $tipeve,
            'detail' => mb_substr($detail, 0, 500),
            'codmod' => mb_substr($codmod, 0, 60),
            'period' => $now->format('Ym'),
            'anopro' => $now->format('Y'),
            'mespro' => $now->format('m'),
            'diapro' => $now->format('d'),
            'fecope' => $now->toDateString(),
            'old_values' => $old,
            'new_values' => $new,
            'codemp' => $companyId ?? (session()->has('company_id') ? (int) session('company_id') : null),
            'host'   => gethostname() ?: 'CONSOLE',
            'codope' => $operator,
            'basdat' => $basdat ?? config('database.connections.mysql.database'),
        ]);
    }
}
