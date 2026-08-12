<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\HsnCode;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\UnitType;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class ItemMasterService
{
    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'sale_order_items', 'work_orders', 'work_order_items', 'work_process_requirements',
        'work_purchase_requirements', 'work_inspection_details', 'warehouse_in_items',
        'warehouse_out_items', 'warehouse_balance_items', 'warehouse_item_stocks',
        'purchase_items', 'purchase_order_items', 'stock_mill_dispatch_items',
        'receive_stock_mill_dispatch_items', 'stock_mill_return_items', 'gate_passes',
        'item_yarn_requirements',
    ];

    public function __construct(private readonly DatabaseManager $database)
    {
        // Dependencies are intentionally constructor-injected.
    }

    public function create(array $attributes, Request $request): Item
    {
        return $this->database->transaction(function () use ($attributes, $request): Item {
            $this->assertMasters($attributes, false);
            $item = Item::make();
            $this->fill($item, $attributes);
            $item->save();
            $this->audit($request, 'created', $item, null, $this->values($item));

            return $item;
        });
    }

    public function update(Item $item, array $attributes, Request $request): Item
    {
        return $this->database->transaction(function () use ($item, $attributes, $request): Item {
            $this->assertMasters($attributes, false, $item);
            $old = $this->values($item);
            if ((int) $attributes['item_type_id'] !== (int) $item->item_type_id && $this->isReferenced($item)) {
                throw ValidationException::withMessages(['item_type_id' => 'Referenced Items cannot be reclassified. Create a new Item instead.']);
            }
            $this->fill($item, $attributes);
            $item->save();
            $this->audit($request, 'updated', $item, $old, $this->values($item));

            return $item;
        });
    }

    public function deleteOrDeactivate(Item $item, Request $request): string
    {
        return $this->database->transaction(function () use ($item, $request): string {
            $old = $this->values($item);
            $referenced = $this->isReferenced($item);
            $item->status = $referenced ? RecordStatus::Inactive->value : RecordStatus::Deleted->value;
            $item->modified = now();
            $item->modified_by = auth('admin')->id();
            $item->save();
            $this->audit($request, $referenced ? 'deactivated' : 'deleted', $item, $old, $this->values($item));

            return $item->status;
        });
    }

    public function isReferenced(Item $item): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'item_id')
                && $this->database->table($table)->where('item_id', $item->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function assertMasters(array $attributes, bool $allowInactive, ?Item $item = null): void
    {
        $type = ItemType::query()->whereKey($attributes['item_type_id'])->notDeleted()->first();
        if (! $type || (! $allowInactive && $type->status !== RecordStatus::Active->value && (int) $type->getKey() !== (int) ($item?->item_type_id))) {
            throw ValidationException::withMessages(['item_type_id' => 'Please select a valid active Item Type.']);
        }
        $unit = UnitType::query()->whereKey($attributes['unit_type_id'])->notDeleted()->first();
        if (! $unit || (! $allowInactive && $unit->status !== RecordStatus::Active->value && (int) $unit->getKey() !== (int) ($item?->unit_type_id))) {
            throw ValidationException::withMessages(['unit_type_id' => 'Please select a valid active Unit.']);
        }
        if (filled($attributes['hsn_code_id'] ?? null) && ! HsnCode::query()->whereKey($attributes['hsn_code_id'])->notDeleted()->exists()) {
            throw ValidationException::withMessages(['hsn_code_id' => 'Please select a valid HSN Code.']);
        }
        if (filled($attributes['gst_rate_id'] ?? null) && ! $this->database->table('gst_rates')->where('gst_rate_id', $attributes['gst_rate_id'])->where('status', '!=', 'Deleted')->exists()) {
            throw ValidationException::withMessages(['gst_rate_id' => 'Please select a valid GST Rate.']);
        }
    }

    private function fill(Item $item, array $attributes): void
    {
        foreach ($attributes as $field => $value) {
            if (Schema::hasColumn('items', $field)) {
                $item->{$field} = is_string($value) ? trim($value) : $value;
            }
        }
        $item->item_code = filled($item->item_code) ? strtoupper(trim((string) $item->item_code)) : null;
        $item->is_conusmable = (int) ($attributes['is_conusmable'] ?? 0);
        $item->is_outsourced = (int) ($attributes['is_outsourced'] ?? 0);
        $item->is_jobwork = (int) ($attributes['is_jobwork'] ?? 0);
        $item->created = $item->created ?: now();
        $item->modified = now();
        $item->created_by = $item->created_by ?: auth('admin')->id();
        $item->modified_by = auth('admin')->id();
    }

    private function values(Item $item): array
    {
        return $item->only(['item_id', 'item_name', 'item_code', 'item_type_id', 'unit_type_id', 'hsncode', 'hsn_code_id', 'gst_rate_id', 'status']);
    }

    private function audit(Request $request, string $event, Item $item, ?array $old, array $new): void
    {
        app(AuditLogger::class)->recordAfterCommit([
            'module' => 'items', 'action' => $event === 'created' ? 'create' : 'update', 'event' => 'item_'.$event,
            'description' => 'Item '.$event.'.', 'auditable_type' => $item->getMorphClass(), 'auditable_id' => $item->getKey(),
            'old_values' => $old, 'new_values' => $new, 'request' => $request,
        ]);
    }
}
