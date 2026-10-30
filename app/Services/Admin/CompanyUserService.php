<?php

namespace App\Services\Admin;

use App\Models\Support\Role;
use App\Models\Support\UserCompanyContext;
use App\Models\Support\UserRole;
use App\Models\Tenant\Profile;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Usuarios de la empresa activa, gestionados por su administrador.
 *
 * Reglas (los usuarios son globales y pueden pertenecer a varias empresas):
 *  - nunca se ven ni se tocan usuarios de soporte;
 *  - código, nombre, correo y contraseña solo se cambian si el usuario pertenece ÚNICAMENTE a esta empresa;
 *  - en cualquier caso se puede cambiar su rol y perfil aquí, o quitarlo de esta empresa;
 *  - inactivar al usuario (global) solo si es exclusivo de esta empresa;
 *  - el administrador no puede quitarse a sí mismo ni cambiar su propio rol.
 */
class CompanyUserService extends BaseService
{
    protected string $model = User::class;

    protected string $label = 'Usuario';

    protected array $searchable = ['code', 'name', 'email'];

    protected array $sortable = ['id', 'code', 'name', 'email', 'state'];

    protected function query(): Builder
    {
        $supportIds = UserRole::query()
            ->whereHas('role', fn ($q) => $q->where('code', 'SOPORTE'))
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', UserCompanyContext::query()->where('company_id', $this->companyId())->select('user_id'))
            ->whereNotIn('id', $supportIds);
    }

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        $paginator = $this->paginate($filters, $perPage);
        $ids = $paginator->getCollection()->pluck('id');

        $contexts = UserCompanyContext::query()
            ->with('role')
            ->where('company_id', $this->companyId())
            ->whereIn('user_id', $ids)
            ->get()
            ->keyBy('user_id');

        $companies = UserCompanyContext::query()
            ->whereIn('user_id', $ids)
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $profiles = Profile::query()
            ->whereIn('codmov', $contexts->pluck('profile_id')->filter()->unique())
            ->pluck('name', 'codmov');

        return $paginator->through(function (User $user) use ($contexts, $companies, $profiles) {
            $context = $contexts->get($user->id);

            return [
                'id' => (int) $user->id,
                'code' => $user->code,
                'name' => $user->name,
                'email' => $user->email,
                'state' => (int) $user->state,
                'role_id' => $context ? (int) $context->role_id : null,
                'role' => $context?->role?->code,
                'profile_id' => $context?->profile_id ? (int) $context->profile_id : null,
                'profile' => $context?->profile_id ? ($profiles[$context->profile_id] ?? null) : null,
                'exclusive' => (int) ($companies[$user->id] ?? 0) === 1,
                'is_self' => (int) $user->id === (int) auth()->id(),
            ];
        });
    }

    public function store(array $payload): array
    {
        $role = $this->role((int) $payload['role_id']);

        return DB::transaction(function () use ($payload, $role) {
            $user = new User();
            $user->forceFill([
                'code' => $payload['code'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password'],
                'state' => 1,
                'email_verified_at' => now(),
            ])->save();

            UserCompanyContext::query()->create([
                'user_id' => $user->id,
                'company_id' => $this->companyId(),
                'role_id' => $role->codmov,
                'profile_id' => $role->code === 'USER' ? (int) $payload['profile_id'] : null,
            ]);

            return ['data' => $user, 'flash' => $this->toast('Usuario creado correctamente.')];
        });
    }

    public function update(Model $record, array $payload): array
    {
        /** @var User $user */
        $user = $record;
        $context = $this->contextFor($user);
        $role = $this->role((int) $payload['role_id']);

        if ((int) $user->id === (int) auth()->id() && $role->code !== 'ADMIN') {
            throw ValidationException::withMessages(['role_id' => 'No puedes quitarte el rol de administrador a ti mismo.']);
        }

        return DB::transaction(function () use ($user, $context, $role, $payload) {
            if ($this->isExclusive($user)) {
                $data = array_intersect_key($payload, array_flip(['code', 'name', 'email']));

                if (! empty($payload['password'])) {
                    $data['password'] = $payload['password'];
                }

                if ($data) {
                    $user->forceFill($data)->save();
                }
            }

            $context->forceFill([
                'role_id' => $role->codmov,
                'profile_id' => $role->code === 'USER' ? (int) $payload['profile_id'] : null,
            ])->save();

            return ['data' => $user, 'flash' => $this->toast('Usuario actualizado correctamente.')];
        });
    }

    /** Activa o inactiva al usuario (global): solo si pertenece únicamente a esta empresa. */
    public function toggleState(Model $record): array
    {
        $this->contextFor($record);

        if ((int) $record->id === (int) auth()->id()) {
            return ['data' => $record, 'flash' => $this->toast('No puedes inactivar tu propio usuario.', 'error')];
        }

        if (! $this->isExclusive($record)) {
            return ['data' => $record, 'flash' => $this->toast('El usuario también pertenece a otras empresas: solo soporte puede inactivarlo. Puedes quitarlo de esta empresa.', 'error')];
        }

        return parent::toggleState($record);
    }

    /** Quita al usuario de esta empresa (no borra su cuenta). */
    public function detach(User $user): array
    {
        $context = $this->contextFor($user);

        if ((int) $user->id === (int) auth()->id()) {
            return ['data' => $user, 'flash' => $this->toast('No puedes quitarte a ti mismo de la empresa.', 'error')];
        }

        $context->delete();

        return ['data' => $user, 'flash' => $this->toast('Usuario quitado de la empresa.')];
    }

    /** Contexto del usuario en ESTA empresa; 404 si no pertenece o es de soporte. */
    protected function contextFor(Model $user): UserCompanyContext
    {
        $isSupport = UserRole::query()
            ->where('user_id', $user->id)
            ->whereHas('role', fn ($q) => $q->where('code', 'SOPORTE'))
            ->exists();

        abort_if($isSupport, 404);

        return UserCompanyContext::query()
            ->where('user_id', $user->id)
            ->where('company_id', $this->companyId())
            ->firstOrFail();
    }

    protected function isExclusive(Model $user): bool
    {
        return UserCompanyContext::query()->where('user_id', $user->id)->count() === 1;
    }

    protected function role(int $roleId): Role
    {
        $role = Role::query()->findOrFail($roleId);

        abort_unless(in_array($role->code, ['ADMIN', 'USER'], true), 422, 'Rol no permitido.');

        return $role;
    }

    protected function companyId(): int
    {
        return (int) session('company_id');
    }
}
