<?php

namespace App\Services;

use App\Models\FabricQuality;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class FabricQualityMasterService
{
    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'items', 'sale_order_items', 'work_order_items', 'work_process_requirements',
        'work_purchase_requirements', 'work_inspection_details', 'warehouse_in_items',
        'warehouse_out_items', 'warehouse_balance_items', 'warehouse_item_stocks',
        'purchase_items', 'purchase_order_items', 'stock_mill_dispatch_items',
        'receive_stock_mill_dispatch_items', 'stock_mill_return_items',
    ];

    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly DatabaseManager $database,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public function save(FabricQuality $quality, array $attributes): FabricQuality
    {
        $creating = ! $quality->exists;
        $before = $creating ? null : $this->snapshot($quality);
        $name = trim((string) $attributes['quality_name']);
        $code = strtoupper(trim((string) ($attributes['quality_code'] ?? '')));
        $gsm = filled($attributes['gsm'] ?? null) ? trim((string) $attributes['gsm']) : '';
        $width = filled($attributes['width'] ?? null) ? trim((string) $attributes['width']) : '';
        $companyId = $this->organization->companyId();

        $duplicate = FabricQuality::query()->where('company_id', $companyId)->where('status', '!=', 'Deleted')
            ->where(function ($query) use ($name, $code, $gsm, $width): void {
                $query->whereRaw('LOWER(TRIM(quality_name)) = ?', [strtolower($name)])
                    ->whereRaw('LOWER(TRIM(COALESCE(gsm, \'\'))) = ?', [strtolower($gsm)])
                    ->whereRaw('LOWER(TRIM(COALESCE(width, \'\'))) = ?', [strtolower($width)]);
                if ($code !== '') {
                    $query->orWhere('quality_code', $code);
                }
            })
            ->when($quality->exists, fn ($query) => $query->whereKey('!=', $quality->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['quality_name' => 'This Fabric Quality identity or code already exists.']);
        }

        $this->protectIdentity($quality, $attributes);
        $quality->fill([
            'company_id' => $companyId, 'quality_name' => $name, 'quality_code' => $code !== '' ? $code : null,
            'description' => filled($attributes['description'] ?? null) ? trim((string) $attributes['description']) : null,
            'gsm' => filled($attributes['gsm'] ?? null) ? trim((string) $attributes['gsm']) : null,
            'width' => filled($attributes['width'] ?? null) ? trim((string) $attributes['width']) : null,
            'display_order' => $attributes['display_order'] ?? null, 'status' => $attributes['status'],
            'updated_at' => now(),
        ]);
        if ($creating) {
            $quality->created_at = now();
        }
        $quality->save();
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'fabric_quality_created' : 'fabric_quality_updated',
            'description' => 'Fabric Quality master saved.', 'auditable_type' => $quality->getMorphClass(),
            'auditable_id' => $quality->getKey(), 'old_values' => $before,
            'new_values' => $this->snapshot($quality->fresh()),
        ]);

        return $quality->fresh();
    }

    public function transition(FabricQuality $quality, string $status): void
    {
        $before = ['status' => $quality->getRawOriginal('status')];
        $quality->update(['status' => $status, 'updated_at' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => strtolower($status), 'event' => 'fabric_quality_'.strtolower($status),
            'description' => 'Fabric Quality status changed.', 'auditable_type' => $quality->getMorphClass(),
            'auditable_id' => $quality->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status],
        ]);
    }

    public function rejectDeletion(FabricQuality $quality): never
    {
        if ($this->isReferenced($quality)) {
            throw ValidationException::withMessages(['quality' => 'Referenced Fabric Qualities cannot be deleted; deactivate them instead.']);
        }
        throw ValidationException::withMessages(['quality' => 'Fabric Quality identities are retained for history; deactivate them instead.']);
    }

    public function isReferenced(FabricQuality $quality): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            foreach (['fabric_quality_id', 'quality_id'] as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                    && $this->database->table($table)->where($column, $quality->getKey())->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function snapshot(FabricQuality $quality): array
    {
        return $quality->only(['id', 'quality_name', 'quality_code', 'description', 'gsm', 'width', 'display_order', 'status']);
    }

    /** @param array<string, mixed> $attributes */
    private function protectIdentity(FabricQuality $quality, array $attributes): void
    {
        if (! $quality->exists || ! $this->isReferenced($quality)) {
            return;
        }
        foreach (['quality_name', 'quality_code', 'gsm', 'width'] as $field) {
            if (array_key_exists($field, $attributes) && trim((string) ($quality->getRawOriginal($field) ?? '')) !== trim((string) ($attributes[$field] ?? ''))) {
                throw ValidationException::withMessages([$field => 'Referenced Fabric Quality identity fields cannot be changed.']);
            }
        }
    }
}
