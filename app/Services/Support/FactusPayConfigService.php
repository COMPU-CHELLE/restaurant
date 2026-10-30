<?php

namespace App\Services\Support;

use App\Models\Support\FactusPayService as PayConfig;
use App\Services\BaseService;
use App\Services\Factus\FactusPayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Credenciales de Factus Pay por empresa y ambiente. El host nunca se guarda: sale de config('factus.pay_defaults'). */
class FactusPayConfigService extends BaseService
{
    protected string $model = PayConfig::class;

    protected string $label = 'Configuración de Factus Pay';

    protected array $sortable = ['codmov', 'environment', 'email', 'active'];

    protected array $fields = ['codemp', 'environment', 'email', 'password'];

    public function toRow(PayConfig $config): array
    {
        return [
            'id' => (int) $config->codmov,
            'company_id' => (int) $config->codemp,
            'company_code' => $config->company?->code,
            'company_name' => $config->company?->empnom,
            'environment' => $config->environment,
            'email' => $config->email,
            'active' => (bool) $config->active,
            'has_token' => ! empty($config->token),
        ];
    }

    public function store(array $payload): array
    {
        return $this->transaction(function () use ($payload) {
            $data = $this->onlyFields($payload);
            $data['base_url'] = config('factus.pay_defaults.' . $data['environment']);

            $record = new PayConfig();
            $record->forceFill($data + ['active' => ! empty($payload['active'])])->save();

            return ['data' => $record, 'flash' => $this->toast('Configuración de Factus Pay creada correctamente.')];
        });
    }

    public function update(Model $record, array $payload): array
    {
        /** @var PayConfig $record */
        return $this->transaction(function () use ($record, $payload) {
            $data = $this->onlyFields($payload);

            if (empty($data['password'])) {
                unset($data['password']);
            }

            $data['base_url'] = config('factus.pay_defaults.' . $data['environment']);

            // Si cambió el acceso, el token guardado ya no sirve.
            $changed = collect(['codemp', 'environment', 'email', 'password'])
                ->contains(fn ($f) => array_key_exists($f, $data) && (string) $data[$f] !== (string) $record->{$f});

            $record->forceFill($data + [
                'active' => array_key_exists('active', $payload) ? (bool) $payload['active'] : (bool) $record->active,
            ] + ($changed ? ['token' => null] : []))->save();

            return ['data' => $record, 'flash' => $this->toast('Configuración de Factus Pay actualizada correctamente.')];
        });
    }

    public function toggleState(Model $record): array
    {
        $record->forceFill(['active' => ! $record->active])->save();

        return ['data' => $record, 'flash' => $this->toast($record->active ? 'Factus Pay activado.' : 'Factus Pay inactivado.')];
    }

    /** Prueba con los datos del formulario (sin guardar) o con los guardados. @return array{success:bool,error?:string} */
    public function testConnection(array $data, ?PayConfig $stored = null): array
    {
        $env = $data['environment'] ?? $stored?->environment;
        $email = $data['email'] ?? $stored?->email;
        $password = ! empty($data['password']) ? $data['password'] : $stored?->password;

        if (! $env || ! $email || ! $password) {
            return ['success' => false, 'error' => 'Completa ambiente, correo y contraseña.'];
        }

        // Solo se persiste el token si se probó exactamente lo guardado.
        $same = $stored && $env === $stored->environment && $email === $stored->email && $password === $stored->password;
        $config = $same ? $stored : new PayConfig(['environment' => $env, 'email' => $email, 'password' => $password]);

        return app(FactusPayService::class)->testLogin($config);
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

        $query->where(fn (Builder $q) => $q->where('email', 'like', $like)
            ->orWhereHas('company', fn (Builder $c) => $c->where('empnom', 'like', $like)->orWhere('code', 'like', $like)));
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['state']) && in_array((string) $filters['state'], ['0', '1'], true)) {
            $query->where('active', (int) $filters['state']);
        }
    }
}
