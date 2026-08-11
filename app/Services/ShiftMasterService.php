<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\Factory;
use App\Models\Shift;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class ShiftMasterService
{
    private const REFERENCE_TABLES = ['individuals', 'users', 'work_orders', 'attendance', 'production_plans', 'machine_schedules'];

    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly DatabaseManager $database, private readonly AuditLogger $audit)
    {
    }

    public function save(Shift $shift, array $attributes, Request $request): Shift
    {
        return DB::transaction(function () use ($shift, $attributes, $request): Shift {
            $factoryId = $attributes['factory_id'] ?? null;
            if ($factoryId !== null && ! Factory::query()->whereKey($factoryId)->where('company_id', $this->organization->companyId())->active()->exists()) {
                throw ValidationException::withMessages(['factory_id' => 'The selected factory is not available.']);
            }
            $name = trim((string) ($attributes['shift_name'] ?? ''));
            $code = trim((string) ($attributes['shift_code'] ?? '')) ?: null;
            $start = $this->time($attributes['start_time'] ?? null, 'start_time');
            $end = $this->time($attributes['end_time'] ?? null, 'end_time');
            if ($start === $end) {
                throw ValidationException::withMessages(['end_time' => 'Start and End Time must define a meaningful Shift.']);
            }
            $duplicate = Shift::query()->where('company_id', $this->organization->companyId())->where('status', '!=', RecordStatus::Deleted->value)
                ->when($factoryId === null, fn ($q) => $q->whereNull('factory_id'), fn ($q) => $q->where('factory_id', $factoryId))
                ->whereRaw('LOWER(TRIM(shift_name)) = ?', [strtolower($name)])
                ->when($shift->exists, fn ($q) => $q->where('id', '!=', $shift->getKey()))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['shift_name' => 'This Shift already exists for the selected scope.']);
            }
            if ($code !== null && Shift::query()->where('company_id', $this->organization->companyId())->where('status', '!=', RecordStatus::Deleted->value)->whereRaw('LOWER(TRIM(shift_code)) = ?', [strtolower($code)])
                ->when($factoryId === null, fn ($q) => $q->whereNull('factory_id'), fn ($q) => $q->where('factory_id', $factoryId))
                ->when($shift->exists, fn ($q) => $q->where('id', '!=', $shift->getKey()))->exists()) {
                throw ValidationException::withMessages(['shift_code' => 'This Shift Code already exists for the selected scope.']);
            }
            if ($shift->exists && $this->isReferenced($shift)) {
                foreach (['shift_name' => 'Name', 'shift_code' => 'Code', 'start_time' => 'Start Time', 'end_time' => 'End Time', 'factory_id' => 'Factory'] as $field => $label) {
                    $old = $field === 'start_time' || $field === 'end_time' ? substr((string) $shift->{$field}, 0, 5) : (string) ($shift->{$field} ?? '');
                    $new = (string) ($attributes[$field] ?? '');
                    if ($old !== $new) {
                        throw ValidationException::withMessages([$field => "Referenced Shift {$label} cannot be changed because it would alter historical meaning."]);
                    }
                }
            }
            $old = $shift->exists ? $shift->getAttributes() : null;
            $shift->fill(['factory_id' => $factoryId, 'shift_name' => $name, 'shift_code' => $code, 'start_time' => $start, 'end_time' => $end, 'description' => $attributes['description'] ?? null, 'display_order' => (int) ($attributes['display_order'] ?? 0), 'status' => $attributes['status'] ?? RecordStatus::Active->value]);
            $shift->company_id = $this->organization->companyId();
            $shift->created_by = $shift->created_by ?: auth('admin')->id();
            $shift->modified_by = auth('admin')->id();
            $shift->created = $shift->created ?: now();
            $shift->modified = now();
            $shift->created_at = $shift->created_at ?: now();
            $shift->updated_at = now();
            $shift->save();
            $this->audit->recordAfterCommit(['module' => 'shifts', 'action' => $old ? 'update' : 'create', 'event' => $old ? 'shift_updated' : 'shift_created', 'description' => 'Shift master saved.', 'auditable_type' => $shift->getMorphClass(), 'auditable_id' => $shift->getKey(), 'old_values' => $old, 'new_values' => $shift->getAttributes(), 'request' => $request]);

            return $shift->fresh(['factory']);
        });
    }

    public function transition(Shift $shift, string $status): void
    {
        if ($status === RecordStatus::Inactive->value && $this->hasActiveReference($shift)) {
            throw ValidationException::withMessages(['status' => 'This Shift is assigned to active operational records and cannot be deactivated.']);
        }
        $before = ['status' => $shift->getRawOriginal('status')];
        $shift->status = $status;
        $shift->modified = now();
        $shift->modified_by = auth('admin')->id();
        $shift->save();
        $this->audit->recordAfterCommit(['module' => 'shifts', 'action' => strtolower($status), 'event' => 'shift_'.strtolower($status), 'description' => 'Shift status changed.', 'auditable_type' => $shift->getMorphClass(), 'auditable_id' => $shift->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status]]);
    }

    public function remove(Shift $shift, Request $request): void
    {
        if ($this->isReferenced($shift)) {
            throw ValidationException::withMessages(['shift' => 'Referenced Shifts cannot be deleted; deactivate the Shift instead.']);
        }
        $old = $shift->getAttributes();
        $shift->status = RecordStatus::Deleted->value;
        $shift->modified = now();
        $shift->modified_by = auth('admin')->id();
        $shift->save();
        $this->audit->recordAfterCommit(['module' => 'shifts', 'action' => 'delete', 'event' => 'shift_deleted', 'description' => 'Shift removed.', 'auditable_type' => $shift->getMorphClass(), 'auditable_id' => $shift->getKey(), 'old_values' => $old, 'new_values' => $shift->getAttributes(), 'request' => $request]);
    }

    public function isReferenced(Shift $shift): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'shift_id') && $this->database->table($table)->where('shift_id', $shift->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function hasActiveReference(Shift $shift): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'shift_id') && $this->database->table($table)->where('shift_id', $shift->getKey())->where(function ($q): void {
                $q->whereNull('status')->orWhere('status', '!=', RecordStatus::Deleted->value);
            })->exists()) {
                return true;
            }
        }

        return false;
    }

    private function time(mixed $value, string $field): string
    {
        $value = trim((string) $value);
        $date = \DateTime::createFromFormat('!H:i', $value);
        if (! $date || $date->format('H:i') !== $value) {
            throw ValidationException::withMessages([$field => 'Please enter a valid time in HH:MM format.']);
        }

        return $date->format('H:i:s');
    }
}
