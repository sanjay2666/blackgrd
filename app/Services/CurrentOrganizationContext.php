<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Factory;
use App\Models\UserOrganizationAccess;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CurrentOrganizationContext
{
    private ?UserOrganizationAccess $access = null;

    public function resolve(Request $request): self
    {
        $user = $this->authenticatedPrincipal();
        if (! $user instanceof Authenticatable) {
            throw new RuntimeException('An authenticated organization identity is required.');
        }

        $accessQuery = UserOrganizationAccess::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('status', 'Active')
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });

        $canonicalCompanyId = Company::canonical()->value('id');
        if ($canonicalCompanyId === null) {
            throw new RuntimeException('No canonical active company is configured.');
        }
        $requestedCompany = $request->session()->get('organization.company_id');
        if ($requestedCompany !== null && (int) $requestedCompany !== (int) $canonicalCompanyId) {
            throw new RuntimeException('Company switching is not available in this installation.');
        }
        $requestedFactory = $request->session()->get('organization.factory_id');
        if ($requestedFactory !== null && $requestedCompany === null) {
            throw new RuntimeException('A factory context requires a company context.');
        }
        if ($requestedCompany !== null) {
            $accessQuery->where('company_id', $requestedCompany);
        } else {
            $accessQuery->where('company_id', $canonicalCompanyId)->orderBy('id');
        }
        if ($requestedFactory !== null) {
            $accessQuery->where(function ($query) use ($requestedFactory): void {
                $query->whereNull('factory_id')->orWhere('factory_id', $requestedFactory);
            });
        }

        if ($requestedFactory !== null && $requestedCompany !== null) {
            $accessQuery->whereHas('factory', function ($query) use ($requestedCompany, $requestedFactory): void {
                $query->where('id', $requestedFactory)
                    ->where('company_id', $requestedCompany)
                    ->where('status', 'Active');
            });
        }

        $this->access = $accessQuery->with(['company', 'branch', 'factory', 'department'])->first();
        if ($this->access === null || $this->access->company === null || $this->access->company->status !== 'Active') {
            throw new RuntimeException('No active organization access is available for this identity.');
        }

        $request->attributes->set(self::class, $this);

        return $this;
    }

    public function access(): UserOrganizationAccess
    {
        if ($this->access === null) {
            throw new RuntimeException('Organization context has not been resolved.');
        }

        return $this->access;
    }

    public function company(): Company
    {
        return $this->access()->company;
    }

    public function companyId(): int
    {
        return (int) $this->access()->company_id;
    }

    public function branch(): ?Branch
    {
        return $this->access()->branch;
    }

    public function factory(): ?Factory
    {
        return $this->access()->factory;
    }

    public function factoryId(): ?int
    {
        return $this->access()->factory_id ? (int) $this->access()->factory_id : null;
    }

    public function canUseCompany(int $companyId): bool
    {
        return $this->companyId() === $companyId;
    }

    public function assign(array &$attributes): void
    {
        $attributes['company_id'] = $this->companyId();
        unset($attributes['companyId']);
    }

    private function authenticatedPrincipal(): ?Authenticatable
    {
        if (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        }

        return Auth::guard('web')->user();
    }
}
