<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

trait CrudResponses
{
    /** Filtros de las tablas (búsqueda, orden, estado y tamaño de página) ya saneados. */
    protected function tableFilters(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 15);

        return [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'sort' => $request->input('sort'),
            'direction' => $request->input('direction') === 'asc' ? 'asc' : 'desc',
            'state' => in_array((string) $request->input('state'), ['0', '1'], true) ? (string) $request->input('state') : null,
            'per_page' => in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 15,
        ];
    }

    /** Muestra el mensaje del servicio como toast y vuelve a la pantalla. */
    protected function done(array $result): RedirectResponse
    {
        $flash = $result['flash'] ?? null;

        if ($flash) {
            Inertia::flash('toast', ['type' => $flash['type'] ?? 'success', 'message' => $flash['toast']]);
        }

        return back();
    }
}
