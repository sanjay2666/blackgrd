<?php

namespace App\Services;

use App\Models\Colour;
use App\Models\DyeingColour;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class DyeingColourMasterService
{
    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly DatabaseManager $database,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public function save(DyeingColour $shade, array $attributes): DyeingColour
    {
        $creating = ! $shade->exists;
        $before = $creating ? null : $this->snapshot($shade);
        $companyId = $this->organization->companyId();
        $name = trim((string) $attributes['name']);
        $code = trim((string) ($attributes['code'] ?? ''));
        $baseColourId = (int) $attributes['colour_id'];

        $base = Colour::query()->where('company_id', $companyId)->whereKey($baseColourId)->first();
        if ($base === null || $base->status !== 'Active') {
            throw ValidationException::withMessages(['colour_id' => 'Select an active Base Colour.']);
        }

        $duplicate = self::companyQuery()->where('colour_id', $baseColourId)
            ->where('status', '!=', 'Deleted')
            ->where(function (Builder $query) use ($name, $code): void {
                $query->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)]);
                if ($code !== '') {
                    $query->orWhereRaw('LOWER(TRIM(code)) = ?', [strtolower($code)]);
                }
            })
            ->when($shade->exists, fn (Builder $query) => $query->whereKey('!=', $shade->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This Shade name or code already exists for the selected Base Colour.']);
        }

        $this->protectIdentity($shade, $attributes);
        $shade->fill([
            'company_id' => $companyId,
            'colour_id' => $baseColourId,
            'name' => $name,
            'code' => $code !== '' ? $code : null,
            'description' => trim((string) ($attributes['description'] ?? '')) ?: null,
            'display_order' => (int) ($attributes['display_order'] ?? 0),
            'status' => $attributes['status'],
            'modified' => now(),
        ]);
        if ($creating) {
            $shade->created = now();
        }
        $shade->save();

        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'dyeing_colour_created' : 'dyeing_colour_updated',
            'description' => 'Shade / Dyeing Colour master saved.', 'auditable_type' => $shade->getMorphClass(),
            'auditable_id' => $shade->getKey(), 'old_values' => $before,
            'new_values' => $this->snapshot($shade->fresh()),
        ]);

        return $shade->fresh(['colour']);
    }

    public function transition(DyeingColour $shade, string $status): void
    {
        $before = ['status' => $shade->getRawOriginal('status')];
        $shade->update(['status' => $status, 'modified' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => strtolower($status), 'event' => 'dyeing_colour_'.strtolower($status),
            'description' => 'Shade / Dyeing Colour status changed.', 'auditable_type' => $shade->getMorphClass(),
            'auditable_id' => $shade->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status],
        ]);
    }

    public function rejectDeletion(DyeingColour $shade): never
    {
        $message = $this->isReferenced($shade)
            ? 'Referenced Shades cannot be deleted; deactivate them instead.'
            : 'Shade identities are retained for history; deactivate them instead.';
        throw ValidationException::withMessages(['shade' => $message]);
    }

    public function isReferenced(DyeingColour $shade): bool
    {
        foreach (['sale_order_items', 'work_order_items', 'work_process_requirements', 'warehouse_balance_items', 'warehouse_item_stocks', 'work_inspections', 'lab_tests', 'lab_test_requests'] as $table) {
            foreach (['dyeing_colour_id', 'dyeing_color_id', 'shade_id'] as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                    && $this->database->table($table)->where($column, $shade->getKey())->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function snapshot(DyeingColour $shade): array
    {
        return $shade->only(['id', 'company_id', 'colour_id', 'name', 'code', 'description', 'display_order', 'status']);
    }

    private function protectIdentity(DyeingColour $shade, array $attributes): void
    {
        if (! $shade->exists || ! $this->isReferenced($shade)) {
            return;
        }
        foreach (['name', 'code', 'colour_id'] as $field) {
            if (array_key_exists($field, $attributes)
                && (string) $shade->getRawOriginal($field) !== trim((string) $attributes[$field])) {
                throw ValidationException::withMessages([$field => 'Referenced Shade identity fields cannot be changed.']);
            }
        }
    }

    private function companyQuery(): Builder
    {
        return DyeingColour::query()->where('company_id', $this->organization->companyId());
    }
}
