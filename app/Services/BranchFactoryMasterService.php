<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class BranchFactoryMasterService
{
    public const FIELDS = ['name', 'branch_code', 'factory_code', 'kind', 'branch_id', 'address', 'city', 'state', 'pin_code', 'country', 'phone', 'mobile', 'email', 'contact_person', 'gstin', 'remarks', 'status'];

    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
    ) {
    }

    public function save(Model $location, array $attributes): Model
    {
        $creating = ! $location->exists;
        $before = $location->exists ? $location->only($this->auditedFields($location)) : null;
        $this->organization->assign($attributes);
        $attributes['gstin'] = $this->normalizeGstin($attributes['gstin'] ?? null);
        $attributes = array_intersect_key($attributes, array_flip($this->fieldsFor($location)));
        if ($location instanceof Factory && isset($attributes['branch_id'])) {
            $branch = Branch::query()->whereKey($attributes['branch_id'])->where('company_id', $this->organization->companyId())->where('status', '!=', 'Deleted')->firstOrFail();
            if ($branch->status !== 'Active') {
                abort(422, 'Factory must belong to an active branch.');
            }
        }
        DB::transaction(function () use ($location, $attributes): void {
            $location->fill($attributes);
            $location->save();
        });
        $after = $location->fresh()->only($this->auditedFields($location));
        $this->audit->recordAfterCommit(['module' => 'branches', 'action' => $creating ? 'create' : 'update', 'event' => $creating ? 'location_created' : 'location_updated', 'auditable_type' => $location->getMorphClass(), 'auditable_id' => $location->id, 'description' => 'Branch/factory master saved.', 'old_values' => $before, 'new_values' => $after]);

        return $location->fresh();
    }

    public function transition(Model $location, string $status): void
    {
        $before = ['status' => $location->status?->value ?? $location->getRawOriginal('status')];
        DB::transaction(function () use ($location, $status): void {
            $location->update(['status' => $status]);
        });
        $this->audit->recordAfterCommit(['module' => 'branches', 'action' => strtolower($status), 'event' => 'location_'.strtolower($status), 'auditable_type' => $location->getMorphClass(), 'auditable_id' => $location->id, 'description' => 'Branch/factory status changed.', 'old_values' => $before, 'new_values' => ['status' => $status]]);
    }

    public function fieldsFor(Model $location): array
    {
        $fields = array_diff(self::FIELDS, ['branch_code', 'factory_code', 'kind', 'branch_id']);
        if ($location instanceof Branch) {
            $fields = array_merge($fields, ['branch_code', 'kind']);
        }
        if ($location instanceof Factory) {
            $fields = array_merge($fields, ['factory_code', 'branch_id']);
        }

        return $fields;
    }

    private function auditedFields(Model $location): array
    {
        return array_values(array_intersect($this->fieldsFor($location), ['name', 'branch_code', 'factory_code', 'kind', 'branch_id', 'address', 'city', 'state', 'pin_code', 'country', 'phone', 'mobile', 'email', 'contact_person', 'gstin', 'remarks', 'status']));
    }

    private function normalizeGstin(?string $gstin): ?string
    {
        $gstin = strtoupper(trim((string) $gstin));

        return $gstin === '' ? null : $gstin;
    }
}
