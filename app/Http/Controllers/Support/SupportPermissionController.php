<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\Level;
use App\Models\Support\Module;
use App\Models\Support\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportPermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $query = Permission::query()->with(['module:codmov,code,name', 'level:codmov,code,name,type,parent', 'item:codmov,code,name,type,parent'])->orderBy('codmod')->orderBy('codniv')->orderBy('codite')->orderBy('name');
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('detail', 'like', "%{$search}%"));
        }

        $modules = Module::query()->where('state', 1)->orderBy('name')->get(['codmov', 'code', 'name']);
        $levels = Level::query()->where('state', 1)->orderBy('name')->get(['codmov', 'code', 'name', 'parent', 'type']);

        return Inertia::render('soporte/permisos', [
            'items' => $query->paginate(25)->withQueryString(),
            'modules' => $modules,
            'levels' => $levels,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, int $permission): RedirectResponse
    {
        return $this->save($request, $permission);
    }

    private function save(Request $request, ?int $id = null): RedirectResponse
    {
        $existing = $id ? Permission::query()->findOrFail($id) : null;
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('mysql.permissions', 'code')->ignore($id, 'codmov')],
            'name' => ['required', 'string', 'max:255', Rule::unique('mysql.permissions', 'name')->ignore($id, 'codmov')],
            'detail' => ['nullable', 'string', 'max:255'],
            'codmod' => ['required', 'integer', Rule::exists('mysql.modules', 'codmov')],
            'codniv' => ['nullable', 'integer', Rule::exists('mysql.levels', 'codmov')],
            'codite' => ['nullable', 'integer', Rule::exists('mysql.levels', 'codmov')],
            'state' => ['nullable', 'integer', Rule::in([0, 1])],
        ]);

        $module = Module::query()->findOrFail((int) $validated['codmod']);
        $level = !empty($validated['codniv']) ? Level::query()->findOrFail((int) $validated['codniv']) : null;
        $item = !empty($validated['codite']) ? Level::query()->findOrFail((int) $validated['codite']) : null;

        if ($level && $level->type !== 'LEVEL') {
            return back()->withErrors(['codniv' => 'El submódulo seleccionado no es un nivel de menú.'])->withInput();
        }
        if ($item && $item->type !== 'ITEM') {
            return back()->withErrors(['codite' => 'La opción seleccionada debe ser un ítem de menú.'])->withInput();
        }
        if ($level && $level->parent !== $module->code) {
            return back()->withErrors(['codniv' => 'El submódulo no pertenece al módulo seleccionado.'])->withInput();
        }
        if ($item) {
            $parentLevel = Level::query()->where('code', $item->parent)->first();
            if (!$parentLevel || $parentLevel->type !== 'LEVEL' || !$level || (int) $parentLevel->codmov !== (int) $level->codmov || $parentLevel->parent !== $module->code) {
                return back()->withErrors(['codite' => 'La opción debe pertenecer al submódulo y módulo seleccionados.'])->withInput();
            }
        }
        if ($item && !$level) {
            return back()->withErrors(['codniv' => 'Selecciona el submódulo cuando el permiso corresponde a una opción.'])->withInput();
        }

        DB::connection('mysql')->transaction(function () use ($validated, $request, $existing) {
            $model = $existing ?? new Permission();
            $model->forceFill([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'detail' => $validated['detail'] ?? null,
                'codmod' => (int) $validated['codmod'],
                'codniv' => !empty($validated['codniv']) ? (int) $validated['codniv'] : null,
                'codite' => !empty($validated['codite']) ? (int) $validated['codite'] : null,
                'state' => (int) ($validated['state'] ?? ($model->exists ? $model->state : 1)),
                'host' => $request->ip(),
                'codope' => $request->user()?->code,
                'basdat' => 'support',
            ])->save();
        });

        return back()->with('success', $id ? 'Permiso actualizado correctamente.' : 'Permiso creado correctamente.');
    }

    public function toggle(int $permission): RedirectResponse
    {
        $item = Permission::query()->findOrFail($permission);
        $item->forceFill(['state' => (int) $item->state === 1 ? 0 : 1])->save();
        return back()->with('success', 'Estado del permiso actualizado.');
    }

    public function destroy(int $permission): RedirectResponse
    {
        Permission::query()->findOrFail($permission)->delete();
        return back()->with('success', 'Permiso eliminado correctamente.');
    }
}
