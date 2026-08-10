<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use App\Support\RoutePermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceMappedPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = RoutePermissionRegistry::permission($request->route());
        if ($permission === null && in_array((string) $request->route()?->getName(), config('rbac_routes.excluded_authenticated', []), true)) {
            return $next($request);
        }
        abort_unless($permission !== null && app(AuthorizationService::class)->can($permission), 403);

        return $next($request);
    }
}
