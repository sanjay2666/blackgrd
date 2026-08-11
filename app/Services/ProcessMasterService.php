<?php

namespace App\Services;

use App\Models\Department;
use App\Models\ProcessItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class ProcessMasterService
{
    private const CORE_IDENTITIES = [1 => 'Warping', 2 => 'Weaving', 3 => 'Dyeing', 4 => 'Coating'];

    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'individuals', 'work_orders', 'work_order_items', 'work_process_requirements',
        'process_requirements', 'work_inspections', 'work_inspection_details', 'machines',
        'warehouse_items', 'warehouse_item_stocks', 'stock_mill_dispatches', 'notifications',
    ];

    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
    ) {
    }

    public function save(ProcessItem $process, array $attributes): ProcessItem
    {
        $creating = !$process->exists;
        $before = $creating ? null : $this->snapshot($process);
        $name = trim((string) $attributes['process_name']);
        $code = strtoupper(trim((string) ($attributes['short_code'] ?? '')));
        $companyId = $this->organization->companyId();

        if ($code === '') {
            throw ValidationException::withMessages(['short_code' => 'A stable short code is required.']);
        }

        if ($attributes['department_id'] !== null && !Department::query()
            ->whereKey($attributes['department_id'])
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->exists()) {
            throw ValidationException::withMessages(['department_id' => 'The selected department is not available.']);
        }

        $duplicate = ProcessItem::query()->where('company_id', $companyId)->where('status', '!=', 'Deleted')
            ->where(fn ($query) => $query->whereRaw('LOWER(process_name) = ?', [strtolower($name)])->orWhere('short_code', $code))
            ->when($process->exists, fn ($query) => $query->where('process_items.id', '!=', $process->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['process_name' => 'Process name and short code must be unique.']);
        }

        $this->protectIdentity($process, $name, $code);
        $process->fill([
            'company_id' => $companyId,
            'process_name' => $name,
            'short_code' => $code,
            'description' => $attributes['description'] ?? null,
            'entry_name' => $attributes['entry_name'] ?? null,
            'output_name' => $attributes['output_name'],
            'department_id' => $attributes['department_id'],
            'display_order' => $attributes['display_order'] ?? null,
            'process_sl_no_last' => $attributes['process_sl_no_last'] ?? $process->process_sl_no_last ?? 0,
            'status' => $attributes['status'],
            'modified' => now(),
        ]);
        if ($creating) {
            $process->created = now();
        }
        $process->save();

        $this->audit->recordAfterCommit([
            'module' => 'processes', 'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'process_created' : 'process_updated',
            'auditable_type' => $process->getMorphClass(), 'auditable_id' => $process->id,
            'description' => 'Process master saved.', 'old_values' => $before,
            'new_values' => $this->snapshot($process->fresh()),
        ]);

        return $process->fresh();
    }

    public function transition(ProcessItem $process, string $status): void
    {
        $before = ['status' => $process->getRawOriginal('status')];
        $process->update(['status' => $status, 'modified' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'processes', 'action' => strtolower($status), 'event' => 'process_'.strtolower($status),
            'auditable_type' => $process->getMorphClass(), 'auditable_id' => $process->id,
            'description' => 'Process master status changed.', 'old_values' => $before,
            'new_values' => ['status' => $status],
        ]);
    }

    public function rejectDeletion(ProcessItem $process): never
    {
        throw ValidationException::withMessages(['process' => 'Processes are historical master identities and cannot be deleted; deactivate them instead.']);
    }

    public function isReferenced(ProcessItem $process): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'process_type_id')) {
                if (DB::table($table)->where('process_type_id', $process->id)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function snapshot(Model $process): array
    {
        return $process->only(['process_name', 'short_code', 'description', 'entry_name', 'output_name', 'department_id', 'display_order', 'process_sl_no_last', 'status']);
    }

    private function protectIdentity(ProcessItem $process, string $name, string $code): void
    {
        if (!$process->exists) {
            return;
        }
        $id = (int) $process->getKey();
        if (isset(self::CORE_IDENTITIES[$id]) && (strcasecmp(self::CORE_IDENTITIES[$id], $name) !== 0 || $process->getRawOriginal('process_name') !== $name)) {
            throw ValidationException::withMessages(['process_name' => 'Core process identities cannot be renamed.']);
        }
        if ($this->isReferenced($process) && strtoupper((string) $process->getRawOriginal('short_code')) !== $code) {
            throw ValidationException::withMessages(['short_code' => 'A referenced process short code cannot be changed.']);
        }
    }
}
