<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class CompanyProfileService
{
    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
    ) {
    }

    public function canonical(): Company
    {
        return $this->organization->company();
    }

    /** @param array<string, mixed> $attributes */
    public function update(array $attributes, ?UploadedFile $logo = null): Company
    {
        $company = $this->canonical();
        $before = $company->only($this->auditedFields());
        $oldLogo = $company->logo;
        $newLogo = null;

        if ($logo !== null) {
            $newLogo = $logo->store('company', 'public');
            $attributes['logo'] = $newLogo;
        }

        try {
            DB::transaction(function () use ($company, $attributes): void {
                $company->fill($attributes);
                $company->modified_by = auth('admin')->id();
                $company->save();
            });
        } catch (\Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete($newLogo);
            }
            throw $exception;
        }

        $after = $company->fresh()->only($this->auditedFields());
        $changed = array_values(array_filter($this->auditedFields(), fn (string $field): bool => ($before[$field] ?? null) !== ($after[$field] ?? null)));
        if ($changed !== []) {
            $this->audit->recordAfterCommit([
                'module' => 'company', 'action' => 'update', 'event' => 'company_profile_updated',
                'auditable_type' => $company->getMorphClass(), 'auditable_id' => $company->id,
                'description' => 'Canonical company profile updated.', 'old_values' => array_intersect_key($before, array_flip($changed)),
                'new_values' => array_intersect_key($after, array_flip($changed)), 'changed_fields' => $changed,
            ]);
        }

        if ($newLogo !== null && $oldLogo && str_starts_with($oldLogo, 'company/')) {
            DB::afterCommit(fn () => Storage::disk('public')->delete($oldLogo));
        }

        return $company->fresh();
    }

    /** @return list<string> */
    private function auditedFields(): array
    {
        return ['company_code', 'name', 'legal_name', 'trade_name', 'email', 'alternate_email', 'phone', 'mobile', 'website', 'contact_person_name', 'contact_person_designation', 'contact_person_mobile', 'contact_person_email', 'address_1', 'address_2', 'landmark', 'state_id', 'city_name', 'district_name', 'pincode', 'registration_no', 'pan_no', 'tan_no', 'gstin', 'logo'];
    }
}
