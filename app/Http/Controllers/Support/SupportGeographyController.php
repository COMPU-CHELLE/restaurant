<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\Country;
use App\Models\Support\Zone;
use App\Models\Support\Subzone;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportGeographyController extends Controller
{
    public function countries(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $query = Country::query()->withCount('zones')->orderBy('name');
        if ($search !== '') $query->where(fn ($q) => $q->where('name','like',"%{$search}%")->orWhere('iso2','like',"%{$search}%")->orWhere('iso3','like',"%{$search}%"));
        return Inertia::render('soporte/paises', ['items' => $query->paginate(25)->withQueryString(), 'filters' => ['search' => $search]]);
    }

    public function zones(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $country = $request->query('country');
        $query = Zone::query()->with('country')->withCount('subzones')->orderBy('name');
        if ($search !== '') $query->where(fn ($q) => $q->where('name','like',"%{$search}%")->orWhere('code','like',"%{$search}%"));
        if ($country !== null && $country !== '') $query->where('codcoi', $country);
        return Inertia::render('soporte/zonas', [
            'items' => $query->paginate(25)->withQueryString(),
            'countries' => Country::query()->orderBy('name')->get(['codmov','name','iso2']),
            'filters' => ['search' => $search, 'country' => $country],
        ]);
    }

    public function subzones(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $zone = $request->query('zone');
        $query = Subzone::query()->with('zone.country')->orderBy('name');
        if ($search !== '') $query->where(fn ($q) => $q->where('name','like',"%{$search}%")->orWhere('code','like',"%{$search}%"));
        if ($zone !== null && $zone !== '') $query->where('codzon', $zone);
        return Inertia::render('soporte/subzonas', [
            'items' => $query->paginate(25)->withQueryString(),
            'zones' => Zone::query()->orderBy('name')->get(['codmov','name','code']),
            'filters' => ['search' => $search, 'zone' => $zone],
        ]);
    }
    public function storeCountry(Request $request): RedirectResponse
    {
        return $this->saveGeography($request, 'countries');
    }

    public function updateCountry(Request $request, int $country): RedirectResponse
    {
        return $this->saveGeography($request, 'countries', $country);
    }

    public function toggleCountry(int $country): RedirectResponse
    {
        return $this->toggleGeography('countries', $country);
    }

    public function destroyCountry(int $country): RedirectResponse
    {
        return $this->destroyGeography('countries', $country);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        return $this->saveGeography($request, 'zones');
    }

    public function updateZone(Request $request, int $zone): RedirectResponse
    {
        return $this->saveGeography($request, 'zones', $zone);
    }

    public function toggleZone(int $zone): RedirectResponse
    {
        return $this->toggleGeography('zones', $zone);
    }

    public function destroyZone(int $zone): RedirectResponse
    {
        return $this->destroyGeography('zones', $zone);
    }

    public function storeSubzone(Request $request): RedirectResponse
    {
        return $this->saveGeography($request, 'subzones');
    }

    public function updateSubzone(Request $request, int $subzone): RedirectResponse
    {
        return $this->saveGeography($request, 'subzones', $subzone);
    }

    public function toggleSubzone(int $subzone): RedirectResponse
    {
        return $this->toggleGeography('subzones', $subzone);
    }

    public function destroySubzone(int $subzone): RedirectResponse
    {
        return $this->destroyGeography('subzones', $subzone);
    }

    private function saveGeography(Request $request, string $type, ?int $id = null): RedirectResponse
    {
        $modelClass = match ($type) {
            'countries' => Country::class,
            'zones' => Zone::class,
            'subzones' => Subzone::class,
        };
        $unique = fn (string $column) => Rule::unique("mysql.{$type}", $column)->ignore($id, 'codmov');
        $rules = match ($type) {
            'countries' => [
                'iso2' => ['required', 'string', 'size:2', 'uppercase', $unique('iso2')],
                'iso3' => ['required', 'string', 'size:3', 'uppercase', $unique('iso3')],
                'iso_numeric' => ['required', 'string', 'max:3'],
                'factus_id' => ['nullable', 'string', 'max:30'],
                'name' => ['required', 'string', 'max:255'],
                'region' => ['nullable', 'string', 'max:255'],
                'subregion' => ['nullable', 'string', 'max:255'],
                'fiscal_code' => ['nullable', 'string', 'max:30'],
                'state' => ['nullable', 'integer', Rule::in([0, 1])],
            ],
            'zones' => [
                'code' => ['required', 'string', 'max:30', $unique('code')],
                'factus_id' => ['nullable', 'string', 'max:30'],
                'name' => ['required', 'string', 'max:255'],
                'codcoi' => ['required', 'integer', Rule::exists('mysql.countries', 'codmov')],
                'state' => ['nullable', 'integer', Rule::in([0, 1])],
            ],
            'subzones' => [
                'code' => ['required', 'string', 'max:40', $unique('code')],
                'factus_id' => ['nullable', 'string', 'max:30'],
                'factus_code' => ['nullable', 'string', 'max:30'],
                'iso_numeric' => ['nullable', 'string', 'max:10'],
                'name' => ['required', 'string', 'max:255'],
                'codzon' => ['required', 'integer', Rule::exists('mysql.zones', 'codmov')],
                'state' => ['nullable', 'integer', Rule::in([0, 1])],
            ],
        };
        $validated = $request->validate($rules);
        $model = $id === null ? new $modelClass() : $modelClass::query()->findOrFail($id);
        $model->forceFill($validated + [
            'state' => (int) ($validated['state'] ?? 1),
            'host' => $request->ip(),
            'codope' => $request->user()?->code,
            'basdat' => 'support',
        ])->save();

        return back()->with('success', $id === null ? 'Registro creado correctamente.' : 'Registro actualizado correctamente.');
    }

    private function toggleGeography(string $type, int $id): RedirectResponse
    {
        $modelClass = match ($type) {
            'countries' => Country::class,
            'zones' => Zone::class,
            'subzones' => Subzone::class,
        };
        $item = $modelClass::query()->findOrFail($id);
        $item->forceFill(['state' => (int) $item->state === 1 ? 0 : 1])->save();

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    private function destroyGeography(string $type, int $id): RedirectResponse
    {
        $modelClass = match ($type) {
            'countries' => Country::class,
            'zones' => Zone::class,
            'subzones' => Subzone::class,
        };
        $item = $modelClass::query()->findOrFail($id);
        $item->delete();

        return back()->with('success', 'Registro eliminado correctamente.');
    }

}
