<?php

namespace App\Http\Middleware;

use App\Services\CurrentOrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Keep the application bootable during the additive migration window.
        // Once the access table exists, missing context is always fail-closed.
        if (! Schema::hasTable('user_organization_access')) {
            return $next($request);
        }

        try {
            app(CurrentOrganizationContext::class)->resolve($request);
        } catch (\Throwable $exception) {
            abort(403, 'An active organization context is required.');
        }

        return $next($request);
    }
}
