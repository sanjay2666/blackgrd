<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\ItemYarnRequirement;
use App\Models\ProcessItem;
use App\Models\UnitType;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class YarnRecipeService
{
    public function __construct(private readonly DatabaseManager $database, private readonly AuditLogger $audit)
    {
    }

    public function yarnItemType(): ItemType
    {
        $type = ItemType::query()->whereRaw('LOWER(item_type_name) = ?', ['yarn'])->notDeleted()->first();
        if (! $type) {
            throw ValidationException::withMessages(['yarn_id' => 'The canonical Yarn Item Type is not configured.']);
        }

        return $type;
    }

    public function activeYarns()
    {
        return Item::query()->active()->where('item_type_id', $this->yarnItemType()->getKey())->with('unitType')->orderBy('item_name')->get();
    }

    public function save(ItemYarnRequirement $requirement, array $attributes, Request $request): ItemYarnRequirement
    {
        return $this->database->transaction(function () use ($requirement, $attributes, $request): ItemYarnRequirement {
            $creating = ! $requirement->exists;
            $before = $creating ? null : $this->snapshot($requirement);
            $target = $this->activeItem((int) $attributes['item_id']);
            $yarn = $this->yarn((int) $attributes['yarn_id'], $requirement);
            $process = $this->process((int) $attributes['process_id'], $requirement);
            $unit = $this->unitForYarn($yarn, (string) $attributes['unit']);
            $status = (string) ($attributes['status'] ?? RecordStatus::Active->value);

            if (! in_array($status, [RecordStatus::Active->value, RecordStatus::Inactive->value], true)) {
                throw ValidationException::withMessages(['status' => 'Select a valid recipe status.']);
            }
            if ($status === RecordStatus::Active->value && $this->exactActiveDuplicate($requirement, $target, $yarn, $process, $attributes, $unit)) {
                throw ValidationException::withMessages(['yarn_id' => 'This active Yarn recipe line already exists.']);
            }

            $requirement->fill([
                'item_id' => $target->getKey(), 'yarn_id' => $yarn->getKey(), 'reed_peak' => $attributes['reed_peak'],
                'yarn_quantity' => $attributes['yarn_quantity'], 'unit' => $unit->unit_type_name,
                'process_id' => $process->getKey(), 'status' => $status,
            ]);
            $requirement->financial_year = $requirement->financial_year ?: currentFinancialYear();
            if ($creating) {
                $requirement->created_by = auth('admin')->id();
                $requirement->created_at = now();
            } else {
                $requirement->modified_by = auth('admin')->id();
                $requirement->modified_at = now();
            }
            $requirement->save();

            $this->audit->recordAfterCommit([
                'module' => 'masters', 'action' => $creating ? 'create' : 'update',
                'event' => $creating ? 'yarn_recipe_created' : 'yarn_recipe_updated',
                'description' => 'Yarn recipe requirement saved.', 'auditable_type' => $requirement->getMorphClass(),
                'auditable_id' => $requirement->getKey(), 'old_values' => $before,
                'new_values' => $this->snapshot($requirement->fresh()), 'request' => $request,
            ]);

            return $requirement->fresh();
        });
    }

    public function remove(ItemYarnRequirement $requirement, Request $request): void
    {
        $this->database->transaction(function () use ($requirement, $request): void {
            $before = $this->snapshot($requirement);
            $requirement->status = RecordStatus::Deleted->value;
            $requirement->modified_by = auth('admin')->id();
            $requirement->modified_at = now();
            $requirement->save();
            $this->audit->recordAfterCommit([
                'module' => 'masters', 'action' => 'delete', 'event' => 'yarn_recipe_removed',
                'description' => 'Yarn recipe requirement removed from active configuration.',
                'auditable_type' => $requirement->getMorphClass(), 'auditable_id' => $requirement->getKey(),
                'old_values' => $before, 'new_values' => $this->snapshot($requirement), 'request' => $request,
            ]);
        });
    }

    private function activeItem(int $id): Item
    {
        $item = Item::query()->active()->whereKey($id)->first();
        if (! $item) {
            throw ValidationException::withMessages(['item_id' => 'Select a valid active Item.']);
        }

        return $item;
    }

    private function yarn(int $id, ItemYarnRequirement $current): Item
    {
        $yarn = Item::query()->whereKey($id)->where('item_type_id', $this->yarnItemType()->getKey())->notDeleted()->first();
        if (! $yarn || ($yarn->status !== RecordStatus::Active && (! $current->exists || (int) $current->yarn_id !== $id))) {
            throw ValidationException::withMessages(['yarn_id' => 'Select an active Item classified as Yarn.']);
        }

        return $yarn;
    }

    private function process(int $id, ItemYarnRequirement $current): ProcessItem
    {
        $process = ProcessItem::query()->notDeleted()->whereKey($id)->first();
        if (! $process || ($process->status !== RecordStatus::Active && (! $current->exists || (int) $current->process_id !== $id))) {
            throw ValidationException::withMessages(['process_id' => 'Select a valid active Process.']);
        }

        return $process;
    }

    private function unitForYarn(Item $yarn, string $unit): UnitType
    {
        $canonical = UnitType::query()->notDeleted()->whereKey($yarn->unit_type_id)->first();
        if (! $canonical || strcasecmp(trim($unit), trim($canonical->unit_type_name)) !== 0) {
            throw ValidationException::withMessages(['unit' => 'Recipe Unit must match the Yarn Item Unit.']);
        }

        return $canonical;
    }

    private function exactActiveDuplicate(ItemYarnRequirement $current, Item $target, Item $yarn, ProcessItem $process, array $attributes, UnitType $unit): bool
    {
        return ItemYarnRequirement::query()->where('status', RecordStatus::Active->value)
            ->where('item_id', $target->getKey())->where('process_id', $process->getKey())->where('yarn_id', $yarn->getKey())
            ->where('reed_peak', $attributes['reed_peak'])->where('yarn_quantity', $attributes['yarn_quantity'])
            ->where('unit', $unit->unit_type_name)->when($current->exists, fn ($query) => $query->where('id', '!=', $current->getKey()))->exists();
    }

    private function snapshot(ItemYarnRequirement $requirement): array
    {
        return $requirement->only(['id', 'item_id', 'process_id', 'yarn_id', 'reed_peak', 'yarn_quantity', 'unit', 'status']);
    }
}
