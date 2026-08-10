<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class BootstrapRbacCommand extends Command
{
    protected $signature = 'rbac:bootstrap {--execute : Apply only the two approved deterministic assignments} {--confirm-database= : Must equal the configured database name}';

    protected $description = 'Bootstrap the approved existing Admin and frontend User role assignments';

    public function handle(): int
    {
        if (! $this->option('execute') || $this->option('confirm-database') !== config('database.connections.'.config('database.default').'.database')) {
            $this->error('Dry-run only. Re-run with --execute --confirm-database=<configured database>.');

            return self::INVALID;
        }
        $admin = Admin::query()->whereKey(1)->where('email', 'admin@blackgrd.test')->where('status', 'Active')->first();
        $user = User::query()->whereKey(2)->where('email', 'unsanjay4@gmail.com')->where('status', 'Active')->first();
        $companyId = (int) DB::table('companies')->where('status', 'Active')->orderBy('id')->value('id');
        $adminRole = Role::query()->where('role_key', 'admin')->where('scope', 'Company')->where('panel', 'Admin')->where('status', 'Active')->first();
        $frontendRole = Role::query()->where('role_key', 'frontend-administrator')->where('scope', 'Company')->where('panel', 'Frontend')->where('status', 'Active')->first();
        if (! $admin || ! $user || $companyId < 1 || ! $adminRole || ! $frontendRole) {
            $this->error('Approved bootstrap preconditions failed. No changes made.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($admin, $user, $companyId, $adminRole, $frontendRole): void {
            UserRoleAssignment::updateOrCreate(
                ['principal_type' => 'Admin', 'principal_id' => $admin->getAuthIdentifier(), 'role_id' => $adminRole->getKey()],
                ['company_id' => $companyId, 'status' => 'Active', 'assigned_by' => null, 'revoked_at' => null]
            );
            UserRoleAssignment::updateOrCreate(
                ['principal_type' => 'User', 'principal_id' => $user->getAuthIdentifier(), 'role_id' => $frontendRole->getKey()],
                ['company_id' => $companyId, 'status' => 'Active', 'assigned_by' => null, 'revoked_at' => null]
            );
        });
        $this->info('Admin #1 assigned to Admin; User #2 assigned to Frontend Administrator. Super Admin remains unassigned.');

        return self::SUCCESS;
    }
}
