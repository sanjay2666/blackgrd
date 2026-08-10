<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Role;
use App\Models\UserRoleAssignment;
use Illuminate\Console\Command;

final class AssignSuperAdminCommand extends Command
{
    protected $signature = 'rbac:assign-super-admin {email : Exact email of an existing active Admin account} {--force : Skip the confirmation prompt}';

    protected $description = 'Assign the reserved Super Admin role to an existing active Admin account';

    public function handle(): int
    {
        $admin = Admin::query()->where('email', $this->argument('email'))->where('status', 'Active')->first();
        if (! $admin) {
            $this->error('No active Admin account matched the exact email. No changes made.');

            return self::INVALID;
        }
        $role = Role::query()->where('role_key', 'super-admin')->where('scope', 'System')->where('panel', 'Admin')->where('status', 'Active')->first();
        if (! $role) {
            $this->error('Reserved Super Admin role is unavailable. No changes made.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Assign reserved Super Admin to Admin #'.$admin->getKey().' ('.$admin->email.')?')) {
            $this->info('Cancelled. No changes made.');

            return self::SUCCESS;
        }

        UserRoleAssignment::updateOrCreate(
            ['principal_type' => 'Admin', 'principal_id' => $admin->getAuthIdentifier(), 'role_id' => $role->getKey()],
            ['status' => 'Active', 'company_id' => null, 'assigned_by' => null, 'revoked_at' => null]
        );
        $this->info('Super Admin assignment is active for Admin #'.$admin->getKey().'.');

        return self::SUCCESS;
    }
}
