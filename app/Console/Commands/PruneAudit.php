<?php

namespace App\Console\Commands;

use App\Models\Support\Soflog;
use Illuminate\Console\Command;

class PruneAudit extends Command
{
    protected $signature = 'audit:prune {--days=365 : Conserva este número de días}';

    protected $description = 'Elimina de la bitácora los registros más antiguos';

    public function handle(): int
    {
        $days = max(30, (int) $this->option('days'));

        $deleted = Soflog::query()->where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Registros eliminados: {$deleted} (se conservan los últimos {$days} días).");

        return self::SUCCESS;
    }
}
