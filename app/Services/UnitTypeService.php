<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class UnitTypeService
{
    /** Legacy production paths use these IDs as measurement identities. */
    private const PROTECTED_IDENTITIES = [
        2 => 'Meter',
        4 => 'Kg',
    ];

    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'items', 'item_type', 'warehouse_in_items', 'warehouse_out_items',
        'warehouse_balance_items', 'warehouse_item_stocks', 'sale_order_items',
        'work_order_items', 'work_process_requirements', 'work_purchase_requirements',
        'purchase_items', 'purchase_order_items', 'gate_passes',
        'stock_mill_dispatch_items', 'receive_stock_mill_dispatch_items',
        'stock_mill_return_items', 'greige_receive_stock_item_from_job_works',
    ];

    public function create(array $attributes, Request $request): UnitType
    {
        return DB::transaction(function () use ($attributes, $request): UnitType {
            $unit = new UnitType();
            $this->fill($unit, $attributes);
            $unit->save();
            $this->audit($request, 'created', $unit, null, $unit->getAttributes());

            return $unit;
        });
    }

    public function update(UnitType $unit, array $attributes, Request $request): UnitType
    {
        return DB::transaction(function () use ($attributes, $request, $unit): UnitType {
            $old = $unit->getAttributes();
            $this->protectLegacyIdentity($unit, $attributes);
            $this->fill($unit, $attributes);
            $statusChanged = $unit->isDirty('status');
            $unit->save();
            $event = $statusChanged
                ? 'status_changed'
                : 'updated';
            $this->audit($request, $event, $unit, $old, $unit->getAttributes());

            return $unit;
        });
    }

    public function setStatus(UnitType $unit, RecordStatus $status, Request $request): UnitType
    {
        return $this->update($unit, ['status' => $status->value], $request);
    }

    public function assertCanDelete(UnitType $unit): void
    {
        if ($this->isReferenced($unit)) {
            throw ValidationException::withMessages([
                'unit' => 'Referenced units cannot be deleted. Deactivate the unit instead.',
            ]);
        }
    }

    public function isReferenced(UnitType $unit): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'unit_type_id')
                && DB::table($table)->where('unit_type_id', $unit->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    public static function protectedIdentities(): array
    {
        return self::PROTECTED_IDENTITIES;
    }

    private function fill(UnitType $unit, array $attributes): void
    {
        if (array_key_exists('unit_type_name', $attributes)) {
            $unit->unit_type_name = trim((string) $attributes['unit_type_name']);
        }
        if (array_key_exists('status', $attributes)) {
            $unit->status = RecordStatus::tryFromLegacyValue($attributes['status'])?->value;
        }
        $unit->created = $unit->created ?: now();
        $unit->modified = now();
        if (Schema::hasColumn('unit_type', 'unit_code')) {
            $unitCode = $attributes['unit_code'] ?? null;
            $unit->unit_code = $unitCode !== null && $unitCode !== ''
                ? strtoupper(trim((string) $unitCode)) : null;
        }
        if (Schema::hasColumn('unit_type', 'description')) {
            $unit->description = $attributes['description'] ?? null;
        }
        if (Schema::hasColumn('unit_type', 'decimal_places')) {
            $unit->decimal_places = $attributes['decimal_places'] ?? null;
        }
        if (Schema::hasColumn('unit_type', 'display_order')) {
            $unit->display_order = $attributes['display_order'] ?? 0;
        }
    }

    private function protectLegacyIdentity(UnitType $unit, array $attributes): void
    {
        $expected = self::PROTECTED_IDENTITIES[$unit->getKey()] ?? null;
        if ($expected !== null && array_key_exists('unit_type_name', $attributes)
            && strcasecmp(trim((string) $attributes['unit_type_name']), $expected) !== 0) {
            throw ValidationException::withMessages([
                'unit_type_name' => "Unit {$unit->getKey()} is a protected legacy identity and must remain {$expected}.",
            ]);
        }
    }

    private function audit(Request $request, string $event, UnitType $unit, ?array $old, array $new): void
    {
        app(AuditLogger::class)->recordAfterCommit([
            'module' => 'unit-types', 'action' => $event === 'created' ? 'create' : 'update',
            'event' => $event, 'description' => "Unit {$event}.",
            'auditable_type' => $unit->getMorphClass(), 'auditable_id' => $unit->getKey(),
            'old_values' => $old, 'new_values' => $new, 'request' => $request,
        ]);
    }
}
