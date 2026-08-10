<?php

namespace App\Support;

final class RoleTemplateCatalog
{
    /** @return array<string, list<string>> */
    public static function all(): array
    {
        $frontendAdministrator = FrontendPermissionCatalog::keys();
        $admin = array_values(array_filter(
            PermissionRegistry::companyAdminAssignable(),
            static fn (string $key): bool => $key !== 'organization.switch'
        ));

        return [
            'Admin' => $admin,
            'Frontend Administrator' => $frontendAdministrator,
            'Sales' => ['dashboard.view', 'sale-orders.view', 'sale-orders.create', 'sale-orders.update', 'sale-orders.submit', 'sale-orders.print', 'sale-orders.export'],
            'Purchase' => ['dashboard.view', 'purchases.view', 'purchases.create', 'purchases.update', 'purchases.receive', 'purchases.return', 'purchases.print', 'purchases.export'],
            'Production Manager' => ['dashboard.view', 'work-orders.view', 'work-orders.create', 'work-orders.update', 'work-orders.start', 'work-orders.assign', 'work-orders.complete', 'work-orders.print', 'work-orders.export', 'wpr.view', 'wpr.accept', 'wpr.allot'],
            'Production Operator' => ['dashboard.view', 'work-orders.view', 'work-orders.start', 'wpr.view', 'wpr.accept'],
            'Warehouse Manager' => ['dashboard.view', 'warehouse.view-stock', 'warehouse.create', 'warehouse.update', 'warehouse.receive', 'warehouse.issue', 'warehouse.allot', 'warehouse.return', 'warehouse.adjust', 'warehouse.print', 'warehouse.export', 'warehouse.configure'],
            'Warehouse Operator' => ['dashboard.view', 'warehouse.view-stock', 'warehouse.receive', 'warehouse.issue', 'warehouse.allot', 'warehouse.return'],
            'Quality / Inspection' => ['dashboard.view', 'inspection.view', 'inspection.create', 'inspection.update', 'inspection.inspect', 'inspection.print', 'inspection.export'],
            'Accounts / Management Viewer' => ['dashboard.view', 'reports.view', 'reports.print', 'reports.export', 'financial-years.view', 'sale-orders.view', 'purchases.view', 'work-orders.view', 'warehouse.view-stock'],
        ];
    }
}
