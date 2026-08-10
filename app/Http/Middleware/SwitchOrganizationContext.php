<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwitchOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('company_id') || $request->has('factory_id')) {
            $request->session()->put('organization.company_id', $request->input('company_id'));
            $request->session()->put('organization.factory_id', $request->input('factory_id'));
        }

        return $next($request);
    }
}
