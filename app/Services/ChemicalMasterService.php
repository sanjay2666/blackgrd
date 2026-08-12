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

final class ChemicalMasterService
{
    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'sale_order_items', 'work_orders', 'work_order_items', 'work_process_requirements',
        'work_purchase_requirements', 'work_inspection_details', 'work_inspections',
        'warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items',
        'warehouse_item_stocks', 'purchase_items', 'purchase_order_items',
        'stock_mill_dispatches', 'stock_mill_dispatch_items', 'receive_stock_mill_dispatch_items',
        'stock_mill_returns', 'stock_mill_return_items', 'department_returns', 'gate_passes',
        'item_yarn_requirements',
    ];

    public function __construct(private readonly DatabaseManager $database, private readonly AuditLogger $audit) {}

    public function chemicalItemType(): ItemType
    {
        $type = ItemType::query()->whereRaw('LOWER(TRIM(item_type_name)) = ?', ['chemical'])->notDeleted()->first();
        if (! $type) {
            throw ValidationException::withMessages(['item_type_id' => 'The canonical Chemical Item Type is not configured.']);
        }

        return $type;
    }

    public function activeChemicals()
    {
        return Item::query()->active()->where('item_type_id', $this->chemicalItemType()->getKey())
            ->with(['unitType', 'hsnCode', 'gstRate'])->orderBy('item_name')->get();
    }

    public function create(array $attributes, Request $request): Item
    {
        return $this->database->transaction(function () use ($attributes, $request): Item {
            $type = $this->chemicalItemType();
            $this->assertMasters($attributes, $type);
            $this->assertUnique($attributes);
            $item = new Item;
            $this->fill($item, $attributes, $type);
            $item->save();
            $this->audit->recordAfterCommit($this->auditPayload('chemical_created', 'Chemical created.', $item, null, $this->snapshot($item), $request));

            return $item;
        });
    }

    public function update(Item $item, array $attributes, Request $request): Item
    {
        return $this->database->transaction(function () use ($item, $attributes, $request): Item {
            $type = $this->chemicalItemType();
            $this->assertMasters($attributes, $type, $item);
            $this->assertUnique($attributes, $item);
            $referenced = $this->isReferenced($item);
            if ($referenced && (strcasecmp((string) $item->item_name, (string) $attributes['item_name']) !== 0
                || strtoupper((string) $item->item_code) !== strtoupper((string) ($attributes['item_code'] ?? ''))
                || (int) $item->unit_type_id !== (int) $attributes['unit_type_id'])) {
                throw ValidationException::withMessages(['item_name' => 'Referenced Chemical identity and Unit cannot be changed. Deactivate it and create a new Chemical instead.']);
            }
            $before = $this->snapshot($item);
            $this->fill($item, $attributes, $type);
            $item->save();
            $this->audit->recordAfterCommit($this->auditPayload('chemical_updated', 'Chemical updated.', $item, $before, $this->snapshot($item), $request));

            return $item;
        });
    }

    public function deleteOrDeactivate(Item $item, Request $request): string
    {
        return $this->database->transaction(function () use ($item, $request): string {
            $before = $this->snapshot($item);
            $referenced = $this->isReferenced($item);
            $item->status = $referenced ? RecordStatus::Inactive->value : RecordStatus::Deleted->value;
            $item->modified = now();
            $item->modified_by = auth('admin')->id();
            $item->save();
            $this->audit->recordAfterCommit($this->auditPayload($referenced ? 'chemical_deactivated' : 'chemical_deleted', $referenced ? 'Chemical deactivated.' : 'Chemical deleted.', $item, $before, $this->snapshot($item), $request));

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

    private function assertMasters(array $attributes, ItemType $type, ?Item $item = null): void
    {
        if ((int) $attributes['item_type_id'] !== (int) $type->getKey()) {
            throw ValidationException::withMessages(['item_type_id' => 'Select the canonical Chemical Item Type.']);
        }
        $unit = UnitType::query()->whereKey($attributes['unit_type_id'])->notDeleted()->first();
        if (! $unit || ($unit->status !== RecordStatus::Active->value && (int) $unit->getKey() !== (int) ($item?->unit_type_id))) {
            throw ValidationException::withMessages(['unit_type_id' => 'Please select a valid active Unit.']);
        }
        if (filled($attributes['hsn_code_id'] ?? null) && ! HsnCode::query()->whereKey($attributes['hsn_code_id'])->notDeleted()->exists()) {
            throw ValidationException::withMessages(['hsn_code_id' => 'Please select a valid HSN Code.']);
        }
        if (filled($attributes['gst_rate_id'] ?? null) && ! $this->database->table('gst_rates')->where('gst_rate_id', $attributes['gst_rate_id'])->where('status', '!=', 'Deleted')->exists()) {
            throw ValidationException::withMessages(['gst_rate_id' => 'Please select a valid GST Rate.']);
        }
    }

    private function assertUnique(array $attributes, ?Item $item = null): void
    {
        $name = strtolower(trim((string) $attributes['item_name']));
        $query = Item::query()->where('item_type_id', $attributes['item_type_id'])->notDeleted()->when($item, fn ($q) => $q->where('item_id', '!=', $item->getKey()));
        if ($query->whereRaw('LOWER(TRIM(item_name)) = ?', [$name])->exists()) {
            throw ValidationException::withMessages(['item_name' => 'This Chemical Name already exists.']);
        }
        $code = strtoupper(trim((string) ($attributes['item_code'] ?? '')));
        if ($code !== '' && Item::query()->where('item_code', $code)->notDeleted()->when($item, fn ($q) => $q->where('item_id', '!=', $item->getKey()))->exists()) {
            throw ValidationException::withMessages(['item_code' => 'This Item/Chemical Code already exists.']);
        }
    }

    private function fill(Item $item, array $attributes, ItemType $type): void
    {
        foreach (['item_name', 'item_code', 'unit_type_id', 'hsn_code_id', 'gst_rate_id', 'remarks', 'status'] as $field) {
            $item->{$field} = is_string($attributes[$field] ?? null) ? trim($attributes[$field]) : ($attributes[$field] ?? null);
        }
        $item->item_code = filled($item->item_code) ? strtoupper(trim((string) $item->item_code)) : null;
        $item->item_type_id = $type->getKey();
        $item->is_conusmable = 1;
        $item->is_lab_test_required = $attributes['is_lab_test_required'] ?? 'Yes';
        $item->created = $item->created ?: now();
        $item->modified = now();
        $item->created_by = $item->created_by ?: auth('admin')->id();
        $item->modified_by = auth('admin')->id();
    }

    private function snapshot(Item $item): array
    {
        return $item->only(['item_id', 'item_name', 'item_code', 'item_type_id', 'unit_type_id', 'hsn_code_id', 'gst_rate_id', 'remarks', 'status']);
    }

    private function auditPayload(string $event, string $description, Item $item, ?array $old, array $new, Request $request): array
    {
        return ['module' => 'masters', 'action' => $old === null ? 'create' : 'update', 'event' => $event, 'description' => $description, 'auditable_type' => $item->getMorphClass(), 'auditable_id' => $item->getKey(), 'old_values' => $old, 'new_values' => $new, 'request' => $request];
    }
}
