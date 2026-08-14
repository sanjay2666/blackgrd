<?php

namespace App\Support;

use Illuminate\Routing\Route;

final class RoutePermissionRegistry
{
    // This registry supplies audit labels; it is not a runtime authorization path.
    public static function permission(Route $route): ?string
    {
        $name = (string) ($route->getName() ?? '');
        if (in_array($name, config('rbac_routes.excluded_authenticated', []), true)) {
            return null;
        }
        $named = config('rbac_routes.admin_custom', []) + config('rbac_routes.frontend_named', []);
        if (isset($named[$name])) {
            return $named[$name];
        }
        if (str_starts_with($name, 'admin.')) {
            $parts = explode('.', $name);
            $resource = $parts[1] ?? '';
            $action = end($parts);
            $permission = config('rbac_routes.admin_resources.'.$resource);
            if (! $permission) {
                return null;
            }

            return match ($action) {
                'index', 'show', 'create', 'edit' => $permission.'.'.($action === 'create' ? 'create' : ($action === 'edit' ? 'update' : (in_array($permission, ['warehouse'], true) ? 'view-stock' : 'view'))),
                'store' => $permission.'.create', 'update' => $permission.'.update', 'destroy' => $permission.'.delete', default => null,
            };
        }
        foreach (config('rbac_routes.frontend_uri', []) as $uri => $permission) {
            if ($route->uri() === $uri || str_starts_with($route->uri(), $uri)) {
                return $permission;
            }
        }

        return null;
    }
}
