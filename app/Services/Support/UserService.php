<?php

namespace App\Services\Support;

use App\Models\Support\Role;
use App\Models\Support\UserCompanyContext;
use App\Models\Support\UserRole;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Usuarios globales (base support): datos de acceso, si son de soporte y a qué empresas
 * pertenecen con qué rol (ADMIN o USER). El perfil de un USER lo asigna el administrador de la empresa.
 */
class UserService extends BaseService
{
    protected string $model = User::class;

    protected string $label = 'Usuario';

    protected array $searchable = ['code', 'name', 'email'];

    protected array $sortable = ['id', 'code', 'name', 'email', 'state'];

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        $paginator = $this->paginate($filters, $perPage);
        $ids = $paginator->getCollection()->pluck('id');

        $support = UserRole::query()
            ->whereIn('user_id', $ids)
            ->whereHas('role', fn ($q) => $q->where('code', 'SOPORTE'))
            ->pluck('user_id')
            ->flip();

        $contexts = UserCompanyContext::query()
            ->with(['company', 'role'])
            ->whereIn('user_id', $ids)
            ->get()
            ->groupBy('user_id');

        return $paginator->through(fn (User $user) => [
            'id' => (int) $user->id,
            'code' => $user->code,
            'name' => $user->name,
            'email' => $user->email,
            'state' => (int) $user->state,
            'is_support' => $support->has($user->id),
            'companies' => $contexts->get($user->id, collect())
                ->filter(fn ($c) => $c->company && $c->role)
                ->map(fn ($c) => [
                    'company_id' => (int) $c->company_id,
                    'code' => $c->company->code,
                    'name' => $c->company->empnom,
                    'role_id' => (int) $c->role_id,
                    'role' => $c->role->code,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $user = new User();
            $user->forceFill([
                'code' => $payload['code'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password'],
                'state' => ! empty($payload['state']) ? 1 : 0,
                'email_verified_at' => now(), // lo crea soporte: no necesita verificar el correo
            ])->save();

            $this->syncAccess($user, (bool) ($payload['is_support'] ?? false), $payload['companies'] ?? []);

            return ['data' => $user, 'flash' => $this->toast('Usuario creado correctamente.')];
        });
    }

    public function update(Model $record, array $payload): array
    {
        /** @var User $user */
        $user = $record;
        $isSelf = (int) $user->id === (int) auth()->id();
        $state = ! empty($payload['state']);
        $isSupport = (bool) ($payload['is_support'] ?? false);

        if ($isSelf && ! $state) {
            throw ValidationException::withMessages(['state' => 'No puedes inactivar tu propio usuario.']);
        }

        if ($isSelf && ! $isSupport) {
            throw ValidationException::withMessages(['is_support' => 'No puedes quitarte el acceso de soporte a ti mismo.']);
        }

        return DB::transaction(function () use ($user, $payload, $state, $isSupport) {
            $data = [
                'code' => $payload['code'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'state' => $state ? 1 : 0,
            ];

            if (! empty($payload['password'])) {
                $data['password'] = $payload['password'];
            }

            $user->forceFill($data)->save();

            $this->syncAccess($user, $isSupport, $payload['companies'] ?? []);

            return ['data' => $user, 'flash' => $this->toast('Usuario actualizado correctamente.')];
        });
    }

    public function toggleState(Model $record): array
    {
        if ((int) $record->id === (int) auth()->id()) {
            return ['data' => $record, 'flash' => $this->toast('No puedes inactivar tu propio usuario.', 'error')];
        }

        return parent::toggleState($record);
    }

    /** Sincroniza el rol global de soporte y las empresas (con su rol) del usuario. */
    protected function syncAccess(User $user, bool $isSupport, array $companies): void
    {
        $supportRoleId = Role::query()->where('code', 'SOPORTE')->value('codmov');

        if ($isSupport) {
            UserRole::firstOrCreate(['user_id' => $user->id, 'role_id' => $supportRoleId]);
        } else {
            UserRole::query()->where('user_id', $user->id)->where('role_id', $supportRoleId)->delete();
        }

        $desired = collect($companies)->keyBy(fn ($row) => (int) $row['company_id']);

        UserCompanyContext::query()
            ->where('user_id', $user->id)
            ->whereNotIn('company_id', $desired->keys()->all())
            ->delete();

        foreach ($desired as $companyId => $row) {
            $context = UserCompanyContext::firstOrNew(['user_id' => $user->id, 'company_id' => $companyId]);
            $roleId = (int) $row['role_id'];

            // El perfil pertenece al rol anterior: si el rol cambia, el administrador debe asignarlo de nuevo.
            if ($context->exists && (int) $context->role_id !== $roleId) {
                $context->profile_id = null;
            }

            $context->role_id = $roleId;
            $context->save();
        }
    }
}
