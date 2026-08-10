<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionRegistry;
use App\Support\RoleTemplateCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRbacCommand extends Command
{
    protected $signature = 'rbac:sync {--templates : Also create missing single-company role templates}';

    protected $description = 'Synchronize the canonical RBAC permission registry idempotently';

    public function handle(): int
    {
        foreach (PermissionRegistry::all() as $definition) {
            Permission::updateOrCreate(['permission_key' => $definition['key']], [
                'resource' => $definition['resource'], 'action' => $definition['action'],
                'category' => $definition['category'], 'description' => $definition['description'],
                'is_critical' => $definition['critical'], 'status' => 'Active',
            ]);
        }
        $systemRole = Role::updateOrCreate(['role_key' => 'super-admin'], ['company_id' => null, 'name' => 'Super Admin', 'scope' => 'System', 'panel' => 'Admin', 'description' => 'Reserved system role; assign only through reviewed owner bootstrap.', 'status' => 'Active']);
        $systemRole->permissions()->sync(Permission::where('status', 'Active')->pluck('id')->all());
        if ($this->option('templates')) {
            $companyId = (int) DB::table('companies')->where('status', 'Active')->orderBy('id')->value('id');
            if ($companyId < 1) {
                $this->error('An active company is required to materialize role metadata.');

                return self::INVALID;
            }
            foreach (RoleTemplateCatalog::all() as $name => $keys) {
                $role = Role::updateOrCreate(['role_key' => str()->slug($name)], ['company_id' => $companyId, 'name' => $name, 'scope' => 'Company', 'panel' => $name === 'Admin' ? 'Admin' : 'Frontend', 'status' => 'Active']);
                $role->permissions()->sync(Permission::whereIn('permission_key', $keys)->pluck('id')->all());
            }
            $this->info('Created or synchronized '.count(RoleTemplateCatalog::all()).' unassigned role templates.');
        }
        $this->info('Synchronized '.count(PermissionRegistry::all()).' canonical permissions.');

        return self::SUCCESS;
    }
}
