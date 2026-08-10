<?php

namespace App\Support;

final class PermissionRegistry
{
    /** @return list<array{key:string,resource:string,action:string,category:string,description:string,critical:bool}> */
    public static function all(): array
    {
        $groups = [
            'dashboard' => ['view'], 'organization' => ['view', 'switch', 'access-manage'],
            'companies' => ['view', 'create', 'update', 'delete', 'configure'],
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
}
