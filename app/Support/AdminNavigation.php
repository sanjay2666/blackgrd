<?php

namespace App\Support;

use App\Services\AuthorizationService;

final class AdminNavigation
{
    /** @return list<array{label:string,icon:string,items:list<array{label:string,route:string,permission:string,active:string}>}> */
    public static function visible(AuthorizationService $authorization): array
    {
        $groups = array_map(
            static function (array $group) use ($authorization): array {
                $group['items'] = array_values(array_filter(
                    $group['items'],
                    static fn (array $item): bool => $authorization->can($item['permission'])
                ));

                return $group;
            },
            self::groups()
        );

        return array_values(array_filter($groups, static fn (array $group): bool => $group['items'] !== []));
    }

    /** @return list<array{label:string,icon:string,items:list<array{label:string,route:string,permission:string,active:string}>}> */
    public static function groups(): array
    {
        return [
            ['label' => 'Administration', 'icon' => 'fa-users', 'items' => [
                self::item('User Management', 'admin.users.index', 'users.manage', 'admin.users.*'),
                self::item('Roles', 'admin.roles.index', 'roles.view', 'admin.roles.*'),
            ]],
            ['label' => 'Organization', 'icon' => 'fa-building', 'items' => [
                self::item('Company Profile', 'admin.companies.index', 'companies.view', 'admin.companies.*'),
                self::item('Branches / Factories', 'admin.branches.index', 'branches.view', 'admin.branches.*'),
                self::item('Departments', 'admin.departments.index', 'departments.view', 'admin.departments.*'),
                self::item('Financial Years', 'admin.financial-years.index', 'financial-years.view', 'admin.financial-years.*'),
            ]],
            ['label' => 'Masters', 'icon' => 'fa-database', 'items' => [
                self::item('Individuals (All Parties)', 'admin.individuals.index', 'employees.view', 'admin.individuals.*'),
                self::item('Employee Master', 'admin.employees.index', 'employees.view', 'admin.employees.*'),
                self::item('Customer Master', 'admin.customers.index', 'customers.view', 'admin.customers.*'),
                self::item('Vendor Master', 'admin.vendors.index', 'purchases.view', 'admin.vendors.*'),
                self::item('Transporter Master', 'admin.transporters.index', 'masters.view', 'admin.transporters.*'),
                self::item('States', 'admin.states.index', 'masters.view', 'admin.states.*'),
                self::item('Colours', 'admin.colours.index', 'masters.view', 'admin.colours.*'),
                self::item('Shade / Dyeing Colours', 'admin.dyeing-colours.index', 'masters.view', 'admin.dyeing-colours.*'),
                self::item('Chemicals', 'admin.chemicals.index', 'masters.view', 'admin.chemicals.*'),
                self::item('Coating Types', 'admin.cotings.index', 'masters.view', 'admin.cotings.*'),
                self::item('Couriers', 'admin.couriers.index', 'masters.view', 'admin.couriers.*'),
                self::item('GST Rates', 'admin.gst-rates.index', 'masters.view', 'admin.gst-rates.*'),
                self::item('HSN Master', 'admin.hsn-codes.index', 'masters.view', 'admin.hsn-codes.*'),
                self::item('Item Types', 'admin.item-types.index', 'masters.view', 'admin.item-types.*'),
                self::item('Items', 'admin.items.index', 'masters.view', 'admin.items.*'),
                self::item('Item Yarn Requirements', 'admin.item-yarn-requirements.index', 'masters.view', 'admin.item-yarn-requirements.*'),
                self::item('Fabric Qualities', 'admin.fabric-qualities.index', 'masters.view', 'admin.fabric-qualities.*'),
                self::item('Rejection / Wastage Reasons', 'admin.fabric-fault-reasons.index', 'masters.view', 'admin.fabric-fault-reasons.*'),
                self::item('Printing Designs', 'admin.printing-designs.index', 'masters.view', 'admin.printing-designs.*'),
                self::item('Packaging Types', 'admin.packaging-types.index', 'masters.view', 'admin.packaging-types.*'),
                self::item('Unit Master', 'admin.unit-types.index', 'masters.view', 'admin.unit-types.*'),
                self::item('Processes', 'admin.process-items.index', 'processes.view', 'admin.process-items.*'),
                self::item('Machines', 'admin.machines.index', 'masters.view', 'admin.machines.*'),
                self::item('Machine Capacity', 'admin.machine-capacities.index', 'masters.view', 'admin.machine-capacities.*'),
                self::item('Shifts', 'admin.shifts.index', 'masters.view', 'admin.shifts.*'),
                self::item('Warehouses', 'admin.warehouses.index', 'warehouse.view-stock', 'admin.warehouses.*'),
                self::item('Warehouse Compartments', 'admin.ware-house-compartments.index', 'warehouse.view-stock', 'admin.ware-house-compartments.*'),
            ]],
            ['label' => 'Configuration', 'icon' => 'fa-cogs', 'items' => [
                self::item('All Pages', 'admin.all-pages.index', 'settings.view', 'admin.all-pages.*'),
                self::item('Notifications', 'admin.notifications.index', 'settings.view', 'admin.notifications.*'),
                self::item('User Web Pages', 'admin.user-web-pages.index', 'settings.view', 'admin.user-web-pages.*'),
                self::item('Office IPs', 'admin.office-ips.index', 'settings.view', 'admin.office-ips.*'),
                self::item('Number Series', 'admin.number-series.index', 'number-series.view', 'admin.number-series.*'),
            ]],
            ['label' => 'Security & Audit', 'icon' => 'fa-shield', 'items' => [
                self::item('Audit Logs', 'admin.audit-logs.index', 'audit-logs.view', 'admin.audit-logs.*'),
                self::item('Login Attempts', 'admin.login-attempts.index', 'security.view', 'admin.login-attempts.*'),
                self::item('Login OTPs', 'admin.login-otps.index', 'security.view', 'admin.login-otps.*'),
                self::item('User Activity Logs', 'admin.user-activity-logs.index', 'security.view', 'admin.user-activity-logs.*'),
            ]],
        ];
    }

    /** @return array{label:string,route:string,permission:string,active:string} */
    private static function item(string $label, string $route, string $permission, string $active): array
    {
        return compact('label', 'route', 'permission', 'active');
    }
}
