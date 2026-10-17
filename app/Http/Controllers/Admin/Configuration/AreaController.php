<?php

namespace App\Http\Controllers\Admin\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Area;
use App\Models\Tenant\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    public function index(): Response
    {
        $areas = Area::query()
            ->where('state', 1)
            ->with(['tables' => fn ($query) => $query->where('state', 1)->orderBy('number')])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/configuracion/areas/index', [
            'areas' => $areas,
            'tableStatuses' => [
                'AVAILABLE' => 'Disponible',
                'OCCUPIED' => 'Ocupada',
                'RESERVED' => 'Reservada',
                'PAYMENT_PENDING' => 'Pendiente de pago',
                'DIRTY' => 'Por limpiar',
                'OUT_OF_SERVICE' => 'Fuera de servicio',
            ],
        ]);
    }

    public function storeArea(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'detail' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $base = Str::upper(Str::slug($data['name'], '')) ?: 'ZONA';
        $code = $base;
        $suffix = 1;
        while (Area::withTrashed()->where('code', $code)->exists()) {
            $code = substr($base, 0, 35).$suffix++;
        }

        Area::create($data + ['code' => $code, 'state' => 1]);

        return back()->with('success', 'Zona creada correctamente.');
    }

    public function updateArea(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'detail' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $area->update($data);

        return back()->with('success', 'Zona actualizada correctamente.');
    }

    public function destroyArea(Area $area): RedirectResponse
    {
        if ($area->tables()->exists()) {
            return back()->withErrors(['area' => 'No puedes eliminar una zona que todavía tiene mesas. Mueve o elimina sus mesas primero.']);
        }

        $area->delete();
        return back()->with('success', 'Zona eliminada.');
    }

    public function storeTable(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:9999', Rule::unique('tenant.restaurant_tables', 'number')->where('area_id', $area->codmov)],
            'name' => ['nullable', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'shape' => ['required', Rule::in(['ROUND', 'RECTANGLE', 'SQUARE', 'OVAL'])],
        ]);

        $count = $area->tables()->where('state', 1)->count();
        $area->tables()->create($data + [
            'pos_x' => 24 + (($count % 4) * 150),
            'pos_y' => 24 + (intdiv($count, 4) * 130),
            'width' => $data['shape'] === 'ROUND' || $data['shape'] === 'SQUARE' ? 92 : 120,
            'height' => 92,
            'status' => 'AVAILABLE',
            'state' => 1,
        ]);

        return back()->with('success', 'Mesa creada correctamente.');
    }

    public function updateTable(Request $request, Area $area, RestaurantTable $table): RedirectResponse
    {
        abort_unless((int) $table->area_id === (int) $area->codmov, 404);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'pos_x' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'pos_y' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'shape' => ['sometimes', Rule::in(['ROUND', 'RECTANGLE', 'SQUARE', 'OVAL'])],
            'width' => ['sometimes', 'integer', 'min:60', 'max:240'],
            'height' => ['sometimes', 'integer', 'min:60', 'max:240'],
            'rotation' => ['sometimes', 'integer', 'min:0', 'max:359'],
        ]);

        $table->update($data);
        return back()->with('success', 'Mesa actualizada.');
    }

    public function destroyTable(Area $area, RestaurantTable $table): RedirectResponse
    {
        abort_unless((int) $table->area_id === (int) $area->codmov, 404);
        if ($table->orders()->whereIn('status', ['OPEN', 'IN_PROGRESS', 'PAYMENT_PENDING'])->exists()) {
            return back()->withErrors(['table' => 'No puedes eliminar una mesa con pedidos activos.']);
        }

        $table->delete();
        return back()->with('success', 'Mesa eliminada.');
    }
}
