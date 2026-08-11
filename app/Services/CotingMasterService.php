<?php

namespace App\Services;

use App\Models\Coting;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class CotingMasterService
{
    private const REFERENCE_TABLES = [
        'items', 'sale_order_items', 'work_order_items', 'warehouse_in_items', 'warehouse_out_items',
        'warehouse_balance_items', 'warehouse_item_stocks', 'work_process_requirements', 'work_inspections',
        'work_inspection_details', 'gate_passes', 'stock_mill_dispatch_items', 'receive_stock_mill_dispatch_items',
        'stock_mill_return_items', 'department_returns', 'greige_receive_stock_item_from_job_works',
    ];

    public function __construct(
        private readonly DatabaseManager $database,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public function save(Coting $coting, array $attributes): Coting
    {
        $creating = ! $coting->exists;
        $before = $creating ? null : $this->snapshot($coting);
        $name = trim((string) $attributes['name']);
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));

        $duplicate = Coting::query()->notDeleted()
            ->where(fn (Builder $query) => $query
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
                ->orWhere(function (Builder $query) use ($code): void {
                    if ($code !== '') {
                        $query->whereRaw('UPPER(TRIM(code)) = ?', [$code]);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }))
            ->when($coting->exists, fn (Builder $query) => $query->whereKey('!=', $coting->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This Coating Type name or code already exists.']);
        }

        $this->protectIdentity($coting, $name, $code);
        $coting->fill([
            'name' => $name,
            'code' => $code !== '' ? $code : null,
            'description' => filled($attributes['description'] ?? null) ? trim((string) $attributes['description']) : null,
            'display_order' => $attributes['display_order'] ?? null,
            'status' => $attributes['status'],
            'modified' => now(),
        ]);
        if ($creating) {
            $coting->created = now();
        }
        $coting->save();
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'coating_type_created' : 'coating_type_updated',
            'description' => 'Coating Type master saved.', 'auditable_type' => $coting->getMorphClass(),
            'auditable_id' => $coting->getKey(), 'old_values' => $before,
            'new_values' => $this->snapshot($coting->fresh()),
        ]);

        return $coting->fresh();
    }

    public function transition(Coting $coting, string $status): void
    {
        $before = ['status' => $coting->getRawOriginal('status')];
        $coting->update(['status' => $status, 'modified' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => strtolower($status), 'event' => 'coating_type_'.strtolower($status),
            'description' => 'Coating Type status changed.', 'auditable_type' => $coting->getMorphClass(),
            'auditable_id' => $coting->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status],
        ]);
    }

    public function rejectDeletion(Coting $coting): never
    {
        throw ValidationException::withMessages(['coting' => $this->isReferenced($coting)
            ? 'Referenced Coating Types cannot be deleted; deactivate them instead.'
            : 'Coating Type identities are retained for history; deactivate them instead.']);
    }

    public function isReferenced(Coting $coting): bool
    {
        $values = array_values(array_filter([$coting->getRawOriginal('code'), $coting->getRawOriginal('name')], fn ($v) => filled($v)));
        foreach (self::REFERENCE_TABLES as $table) {
            foreach (['coating_type', 'coated_pvc', 'coating_pvc'] as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                    && $this->database->table($table)->whereIn($column, $values)->exists()) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @return array<string, mixed> */
    public function snapshot(Coting $coting): array
    {
        return $coting->only(['id', 'company_id', 'name', 'code', 'description', 'display_order', 'status']);
    }

    private function protectIdentity(Coting $coting, string $name, string $code): void
    {
        if ($coting->exists && $this->isReferenced($coting)) {
            if (trim((string) $coting->getRawOriginal('name')) !== $name) {
                throw ValidationException::withMessages(['name' => 'Referenced Coating Type identity cannot be changed.']);
            }
            if (strtoupper(trim((string) $coting->getRawOriginal('code'))) !== $code) {
                throw ValidationException::withMessages(['code' => 'Referenced Coating Type identity cannot be changed.']);
            }
        }
    }
}
