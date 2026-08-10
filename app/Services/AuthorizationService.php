<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

final class AuthorizationService
{
    private ?array $permissionCache = null;

    private ?bool $superAdmin = null;

    public function user(): ?Authenticatable
    {
        return Auth::guard($this->panel() === 'Admin' ? 'admin' : 'web')->user();
    }

    public function panel(): string
    {
        return request()->is('admin') || request()->is('admin/*') ? 'Admin' : 'Frontend';
    }

    public function can(string $permission): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }
        $context = app(CurrentOrganizationContext::class);
        try {
            $context->companyId();
        } catch (\Throwable) {
            return false;
        }

        return in_array($permission, $this->permissions(), true);
    }

    /** @return list<string> */
    public function permissions(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }
        $user = $this->user();
        if (! $user instanceof User) {
            return $this->permissionCache = [];
        }
        $this->permissionCache = UserRoleAssignment::query()
            ->join('roles', 'roles.id', '=', 'user_role_assignments.role_id')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('user_role_assignments.principal_type', $user->user_type)
            ->where('user_role_assignments.principal_id', $user->getAuthIdentifier())
            ->where('user_role_assignments.status', 'Active')->where('roles.status', 'Active')->where('permissions.status', 'Active')
            ->whereIn('roles.scope', ['System', 'Company'])
            ->where(function ($q): void {
                $q->where(function ($system): void {
                    $system->where('roles.scope', 'System')->where('roles.panel', 'Admin');
                })->orWhere(function ($ordinary): void {
                    $ordinary->where('roles.scope', 'Company')->where('roles.panel', $this->panel());
                });
            })
            ->where(function ($q): void {
                $q->whereNull('user_role_assignments.starts_at')->orWhere('user_role_assignments.starts_at', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('user_role_assignments.ends_at')->orWhere('user_role_assignments.ends_at', '>=', now());
            })
            ->pluck('permissions.permission_key')->unique()->values()->all();

        return $this->permissionCache;
    }

    public function isSuperAdmin(): bool
    {
        if ($this->superAdmin !== null) {
            return $this->superAdmin;
        }
        $user = $this->user();

        return $this->superAdmin = $this->panel() === 'Admin' && $user instanceof User && UserRoleAssignment::query()->where('principal_type', 'Admin')->where('principal_id', $user->getAuthIdentifier())->where('status', 'Active')->whereHas('role', fn ($q) => $q->where('role_key', 'super-admin')->where('scope', 'System')->where('panel', 'Admin')->where('status', 'Active'))->exists();
    }

    public function forget(): void
    {
        $this->permissionCache = null;
        $this->superAdmin = null;
    }
}
