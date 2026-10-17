<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanySelected
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()) {
            return $next($request);
        }

        if (! session()->has('company_id') && ! $request->routeIs('company.*')) {
            return redirect()->route('company.select');
        }

        return $next($request);
    }
}
