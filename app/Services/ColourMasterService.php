<?php

namespace App\Services;

use App\Models\Colour;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class ColourMasterService
{
    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'items', 'sale_order_items', 'work_order_items', 'work_process_requirements',
        'work_purchase_requirements', 'work_inspection_details', 'warehouse_in_items',
        'warehouse_out_items', 'warehouse_balance_items', 'warehouse_item_stocks',
        'purchase_items', 'purchase_order_items', 'stock_mill_dispatch_items',
        'receive_stock_mill_dispatch_items', 'stock_mill_return_items', 'work_inspections',
        'gate_passes',
    ];

    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly DatabaseManager $database,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public function save(Colour $colour, array $attributes): Colour
    {
        $creating = ! $colour->exists;
        $before = $creating ? null : $this->snapshot($colour);
        $name = trim((string) $attributes['name']);
        $code = trim((string) ($attributes['code'] ?? ''));
        $companyId = $this->organization->companyId();

        $duplicate = Colour::query()->where('company_id', $companyId)->where('status', '!=', 'Deleted')
            ->where(function ($query) use ($name, $code): void {
                $query->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)]);
                if ($code !== '') {
                    $query->orWhereRaw('LOWER(TRIM(code)) = ?', [strtolower($code)]);
                }
            })
            ->when($colour->exists, fn ($query) => $query->whereKey('!=', $colour->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This Colour name or code already exists.']);
        }

        $this->protectIdentity($colour, $attributes);
        $colour->fill([
            'company_id' => $companyId,
            'name' => $name,
            'code' => $code !== '' ? $code : null,
            'status' => $attributes['status'],
            'modified' => now(),
        ]);
        if ($creating) {
            $colour->created = now();
        }
        $colour->save();

        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'colour_created' : 'colour_updated',
            'description' => 'Colour master saved.', 'auditable_type' => $colour->getMorphClass(),
            'auditable_id' => $colour->getKey(), 'old_values' => $before,
            'new_values' => $this->snapshot($colour->fresh()),
        ]);

        return $colour->fresh();
    }

    public function transition(Colour $colour, string $status): void
    {
        $before = ['status' => $colour->getRawOriginal('status')];
        $colour->update(['status' => $status, 'modified' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => strtolower($status), 'event' => 'colour_'.strtolower($status),
            'description' => 'Colour status changed.', 'auditable_type' => $colour->getMorphClass(),
            'auditable_id' => $colour->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status],
        ]);
    }

    public function rejectDeletion(Colour $colour): never
    {
        $message = $this->isReferenced($colour)
            ? 'Referenced Colours cannot be deleted; deactivate them instead.'
            : 'Colour identities are retained for history; deactivate them instead.';
        throw ValidationException::withMessages(['colour' => $message]);
    }

    public function isReferenced(Colour $colour): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            foreach (['colour_id', 'color_id'] as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                    && $this->database->table($table)->where($column, $colour->getKey())->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function snapshot(Colour $colour): array
    {
        return $colour->only(['id', 'company_id', 'name', 'code', 'status']);
    }

    /** @param array<string, mixed> $attributes */
    private function protectIdentity(Colour $colour, array $attributes): void
    {
        if (! $colour->exists || ! $this->isReferenced($colour)) {
            return;
        }
        foreach (['name', 'code'] as $field) {
            if (array_key_exists($field, $attributes)
                && trim((string) ($colour->getRawOriginal($field) ?? '')) !== trim((string) ($attributes[$field] ?? ''))) {
                throw ValidationException::withMessages([$field => 'Referenced Colour identity fields cannot be changed.']);
            }
        }
    }
}
