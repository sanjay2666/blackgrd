<?php

namespace App\Support;

final class FrontendPermissionCatalog
{
    /** Admin-only permissions never appear in Frontend User customization. */
    public static function keys(): array
    {
        return array_values(array_filter(
            array_column(PermissionRegistry::all(), 'key'),
            static fn (string $key): bool => ! in_array(strtok($key, '.'), ['companies', 'roles', 'users', 'security', 'settings', 'audit-logs', 'number-series'], true)
                && $key !== 'organization.access-manage'
        ));
    }
}
