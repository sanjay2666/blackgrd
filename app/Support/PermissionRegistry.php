<?php

namespace App\Support;

final class PermissionRegistry
{
    /** Permissions which must never be granted by a Company Admin. */
    private const SUPER_ADMIN_RESERVED_PREFIXES = ['security.', 'audit-logs.', 'settings.'];

    private const SUPER_ADMIN_RESERVED_KEYS = ['organization.access-manage', 'companies.configure'];

    /** Resources which belong only to the Admin security/configuration panel. */
    private const ADMIN_ONLY_RESOURCES = ['companies', 'roles', 'users', 'security', 'settings', 'audit-logs', 'number-series'];

    /** @return list<array{key:string,resource:string,action:string,category:string,description:string,critical:bool}> */
    public static function all(): array
    {
        $groups = [
            'dashboard' => ['view'], 'organization' => ['view', 'access-manage'],
            'companies' => ['view', 'update', 'configure'],
            'financial-years' => ['view', 'create', 'update', 'delete', 'set-current', 'configure'],
            'departments' => ['view', 'create', 'update', 'delete', 'operate', 'configure'],
            'employees' => ['view', 'create', 'update', 'delete', 'export', 'manage'],
            'masters' => ['view', 'create', 'update', 'delete', 'export', 'configure', 'manage-yarn'],
            'sale-orders' => ['view', 'create', 'update', 'cancel', 'submit', 'print', 'export'],
            'purchases' => ['view', 'create', 'update', 'cancel', 'receive', 'return', 'print', 'export'],
            'work-orders' => ['view', 'create', 'update', 'cancel', 'start', 'complete', 'assign', 'print', 'export'],
            'wpr' => ['view', 'create', 'update', 'cancel', 'accept', 'allot', 'return', 'print', 'export'],
            'inspection' => ['view', 'create', 'update', 'inspect', 'override', 'print', 'export', 'configure'],
            'warehouse' => ['view-stock', 'create', 'update', 'delete', 'receive', 'issue', 'allot', 'return', 'adjust', 'print', 'export', 'configure'],
            'gate-pass' => ['view', 'create', 'update', 'cancel', 'issue', 'receive', 'close', 'print', 'export'],
            'job-work' => ['view', 'create', 'update', 'cancel', 'dispatch', 'receive', 'close', 'print', 'export'],
            'reports' => ['view', 'print', 'export', 'configure'],
            'settings' => ['view', 'create', 'update', 'delete', 'configure'],
            'security' => ['view', 'delete', 'export', 'manage'], 'users' => ['view', 'manage'], 'audit-logs' => ['view', 'export'],
            'roles' => ['view', 'create', 'update', 'delete', 'assign'],
            'number-series' => ['view', 'manage'],
        ];
        $critical = ['organization.access-manage', 'financial-years.set-current', 'roles.assign', 'warehouse.adjust', 'work-orders.complete', 'inspection.override', 'gate-pass.issue', 'gate-pass.cancel'];
        $result = [];
        foreach ($groups as $resource => $actions) {
            foreach ($actions as $action) {
                $key = $resource.'.'.$action;
                $result[] = ['key' => $key, 'resource' => $resource, 'action' => $action, 'category' => $resource, 'description' => ucfirst(str_replace('-', ' ', $resource)).' '.$action, 'critical' => in_array($key, $critical, true) || in_array($action, ['cancel', 'delete'], true)];
            }
        }

        return $result;
    }

    /** @return list<string> */
    public static function superAdminReserved(): array
    {
        return array_values(array_filter(
            array_column(self::all(), 'key'),
            static fn (string $key): bool => in_array($key, self::SUPER_ADMIN_RESERVED_KEYS, true)
                || collect(self::SUPER_ADMIN_RESERVED_PREFIXES)->contains(fn (string $prefix): bool => str_starts_with($key, $prefix))
        ));
    }

    /** @return list<string> */
    public static function companyAdminAssignable(): array
    {
        return array_values(array_diff(array_column(self::all(), 'key'), self::superAdminReserved()));
    }

    /** @return list<string> */
    public static function frontendAssignable(): array
    {
        return array_values(array_filter(
            self::companyAdminAssignable(),
            static fn (string $key): bool => ! in_array(strtok($key, '.'), self::ADMIN_ONLY_RESOURCES, true)
                && $key !== 'organization.access-manage'
        ));
    }

    /** @return list<string> */
    public static function assignableForPanel(string $panel): array
    {
        return $panel === 'Frontend' ? self::frontendAssignable() : self::companyAdminAssignable();
    }

    public static function isAdminOnly(string $permission): bool
    {
        return in_array(strtok($permission, '.'), self::ADMIN_ONLY_RESOURCES, true);
    }

    public static function isSuperAdminReserved(string $permission): bool
    {
        return in_array($permission, self::superAdminReserved(), true);
    }
}
