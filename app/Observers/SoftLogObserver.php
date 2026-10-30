<?php

namespace App\Observers;

use App\Models\Support\Soflog;
use App\Services\Audit\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Observador de auditoría (portado de Chellnova): registra CREATE / UPDATE / DELETE / RESTORE.
 * Diferencias: nunca guarda atributos ocultos del modelo (contraseñas, tokens, secretos), ignora campos
 * ruidosos (estado de aprovisionamiento, tokens de Factus), registra la IP del cliente y la base afectada.
 */
class SoftLogObserver
{
    /** Campos que no se auditan además de los ocultos del modelo. */
    protected array $ignore = [
        'created_at', 'updated_at', 'deleted_at', 'host', 'codope',
        'provision_status', 'provision_step', 'provision_error', 'provisioned_at',
        'token', 'refresh_token', 'token_expires_at',
    ];

    public function created(Model $model): void
    {
        $this->write('CREATE', $model, null, $this->clean($model, $model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->clean($model, $model->getChanges());

        if (! $changes) {
            return;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        $this->write('UPDATE', $model, $old, $changes, array_keys($changes));
    }

    public function deleted(Model $model): void
    {
        $this->write('DELETE', $model, $this->clean($model, $model->getOriginal()), null);
    }

    public function restored(Model $model): void
    {
        $this->write('RESTORE', $model, null, null);
    }

    protected function write(string $type, Model $model, ?array $old, ?array $new, array $fields = []): void
    {
        if ($model instanceof Soflog || ! Auth::check()) {
            return;
        }

        Audit::record(
            $type,
            strtoupper(class_basename($model)),
            $this->detail($type, $model, $fields),
            $old,
            $new,
            $model->getConnection()->getDatabaseName(),
        );
    }

    /** Quita ocultos e ignorados y acorta textos muy largos. */
    protected function clean(Model $model, array $values): array
    {
        $skip = array_merge($this->ignore, $model->getHidden());

        return collect($values)
            ->except($skip)
            ->map(fn ($value) => is_string($value) && mb_strlen($value) > 500 ? mb_substr($value, 0, 500) . '…' : $value)
            ->all();
    }

    protected function detail(string $type, Model $model, array $fields): string
    {
        $entity = class_basename($model);
        $ref = $model->getAttribute('code') ?? $model->getAttribute('name') ?? $model->getKey();
        $label = $ref !== null ? "{$entity} [{$ref}]" : $entity;

        return match ($type) {
            'CREATE' => "Se creó {$label}",
            'DELETE' => "Se eliminó {$label}",
            'RESTORE' => "Se restauró {$label}",
            'UPDATE' => count($fields) === 1
                ? "Se modificó el campo {$fields[0]} de {$label}"
                : "Se modificaron los campos " . implode(', ', $fields) . " de {$label}",
            default => "{$label} {$type}",
        };
    }
}
