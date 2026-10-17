<?php

namespace App\Services\Support;

use App\Models\Support\FactusService;
use App\Services\BaseService;
use App\Services\Factus\FactusAuthService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Configuración de Factus por empresa y ambiente (una sola activa por combinación). */
class FactusConfigService extends BaseService
{
    protected string $model = FactusService::class;

    protected string $label = 'Configuración de Factus';

    protected array $sortable = ['codmov', 'environment', 'username', 'active'];

    protected array $fields = ['codemp', 'environment', 'base_url', 'api_version', 'client_id', 'client_secret', 'username', 'password'];

    protected bool $audited = true;

    /** Configuración activa de una empresa (la usan los servicios de facturación). */
    public function activeFor(int $companyId, string $environment = 'production'): FactusService
    {
        $config = FactusService::query()
            ->where('codemp', $companyId)
            ->where('environment', $environment)
            ->where('active', true)
            ->first();

        if (! $config) {
            throw new RuntimeException('La empresa no tiene una configuración de Factus activa en ese ambiente.');
        }

        return $config;
    }

    public function toRow(FactusService $config): array
    {
        $status = ! $config->token ? 'none' : ($config->tokenExpired() ? 'expired' : 'valid');

        return [
            'id' => (int) $config->codmov,
            'company_id' => (int) $config->codemp,
            'company_code' => $config->company?->code,
            'company_name' => $config->company?->empnom,
            'environment' => $config->environment,
            'base_url' => $config->base_url,
            'api_version' => $config->api_version,
            'client_id' => $config->client_id,
            'username' => $config->username,
            'active' => (bool) $config->active,
            'token_status' => $status,
            'token_expires_at' => $config->token_expires_at?->toIso8601String(),
        ];
    }

    public function store(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $data = $this->onlyFields($payload);
            $active = ! empty($payload['active']);

            if ($active) {
                $this->deactivateOthers((int) $data['codemp'], $data['environment']);
            }

            $record = new FactusService();
            $record->forceFill($this->stamp($data, true) + ['active' => $active, 'active_key' => $active ? 1 : null])->save();

            return ['data' => $record, 'flash' => $this->toast('Configuración de Factus creada correctamente.')];
        });
    }

    public function update(Model $record, array $payload): array
    {
        /** @var FactusService $record */
        return DB::transaction(function () use ($record, $payload) {
            $data = $this->onlyFields($payload);

            // Secretos en blanco = conservar los guardados.
            foreach (['client_secret', 'password'] as $secret) {
                if (empty($data[$secret])) {
                    unset($data[$secret]);
                }
            }

            $active = array_key_exists('active', $payload) ? (bool) $payload['active'] : (bool) $record->active;

            if ($active) {
                $this->deactivateOthers((int) $data['codemp'], $data['environment'], (int) $record->codmov);
            }

            // Si cambió algo del acceso, el token guardado ya no sirve.
            $accessChanged = collect(['codemp', 'environment', 'base_url', 'client_id', 'username', 'client_secret', 'password'])
                ->contains(fn ($field) => array_key_exists($field, $data) && (string) $data[$field] !== (string) $record->{$field});

            $extra = ['active' => $active, 'active_key' => $active ? 1 : null];

            if ($accessChanged) {
                $extra += ['token' => null, 'refresh_token' => null, 'token_expires_at' => null];
            }

            $record->forceFill($this->stamp($data, false) + $extra)->save();

            return ['data' => $record, 'flash' => $this->toast('Configuración de Factus actualizada correctamente.')];
        });
    }

    public function toggleState(Model $record): array
    {
        /** @var FactusService $record */
        return DB::transaction(function () use ($record) {
            $activate = ! $record->active;

            if ($activate) {
                $this->deactivateOthers((int) $record->codemp, $record->environment, (int) $record->codmov);
            }

            $record->forceFill(['active' => $activate, 'active_key' => $activate ? 1 : null])->save();

            return ['data' => $record, 'flash' => $this->toast($activate ? 'Configuración activada.' : 'Configuración inactivada.')];
        });
    }

    /**
     * Prueba la conexión con los datos del formulario (sin guardar) o con los guardados.
     * Si el formulario trae un id y deja los secretos en blanco, usa los guardados.
     *
     * @return array{success:bool, error?:string, expires_in?:int}
     */
    public function testConnection(array $data, ?FactusService $stored = null): array
    {
        $credentials = [
            'base_url' => $data['base_url'] ?? $stored?->base_url,
            'client_id' => $data['client_id'] ?? $stored?->client_id,
            'client_secret' => ! empty($data['client_secret']) ? $data['client_secret'] : $stored?->client_secret,
            'username' => $data['username'] ?? $stored?->username,
            'password' => ! empty($data['password']) ? $data['password'] : $stored?->password,
        ];

        foreach ($credentials as $value) {
            if (empty($value)) {
                return ['success' => false, 'error' => 'Faltan datos: completa URL, client id, client secret, usuario y contraseña.'];
            }
        }

        // Solo guarda el token cuando se probó EXACTAMENTE lo que está guardado.
        $sameAsStored = $stored
            && $credentials['base_url'] === $stored->base_url
            && $credentials['client_id'] === $stored->client_id
            && $credentials['username'] === $stored->username
            && $credentials['client_secret'] === $stored->client_secret
            && $credentials['password'] === $stored->password;

        return app(FactusAuthService::class)->testLogin($credentials, $sameAsStored ? $stored : null);
    }

    protected function query(): Builder
    {
        return parent::query()->with('company');
    }

    protected function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . addcslashes($search, '%_\\') . '%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('username', 'like', $like)
                ->orWhere('client_id', 'like', $like)
                ->orWhereHas('company', fn (Builder $c) => $c->where('empnom', 'like', $like)->orWhere('code', 'like', $like));
        });
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['state']) && in_array((string) $filters['state'], ['0', '1'], true)) {
            $query->where('active', (int) $filters['state']);
        }
    }

    protected function deactivateOthers(int $companyId, string $environment, ?int $exceptId = null): void
    {
        FactusService::query()
            ->where('codemp', $companyId)
            ->where('environment', $environment)
            ->where('active', true)
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->update(['active' => false, 'active_key' => null]);
    }
}
