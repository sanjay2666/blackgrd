<?php

namespace App\Services;

use App\Models\Item;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\UnitType;
use App\Models\WorkOrderItem;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SaleOrderRequirementService
{
    public function assertItemReferences(int $itemId, int $unitTypeId): array
    {
        $item = Item::query()
            ->whereKey($itemId)
            ->where('status', 'Active')
            ->with('itemType')
            ->first();

        if ($item === null || $item->itemType === null || $item->itemType->status !== 'Active') {
            throw ValidationException::withMessages(['item_id_arr' => 'Please select a valid active Item and Item Type.']);
        }

        $unit = UnitType::query()->whereKey($unitTypeId)->where('status', 'Active')->first();
        if ($unit === null) {
            throw ValidationException::withMessages(['unit_type_id_arr' => 'Please select a valid active Unit.']);
        }

        if ($item->unit_type_id !== null && (int) $item->unit_type_id !== $unitTypeId) {
            throw ValidationException::withMessages(['unit_type_id_arr' => 'The selected Unit is not compatible with the Item.']);
        }

        return [$item, $unit];
    }

    public function assertPositiveQuantity(mixed $meter, string $field = 'meter_arr'): void
    {
        if (! is_numeric($meter) || (float) $meter <= 0) {
            throw ValidationException::withMessages([$field => 'Quantity must be greater than zero.']);
        }
    }

    public function assertCanMutate(SaleOrderItem $item): void
    {
        if (WorkOrderItem::query()->where('sale_order_item_id', $item->getKey())->exists()) {
            throw ValidationException::withMessages(['sale_order_item' => 'This Sale Order Item cannot be changed after downstream Work Order history exists.']);
        }
    }

    public function assertCanDeleteOrder(int $saleOrderId): void
    {
        if (WorkOrderItem::query()->whereIn('sale_order_item_id', SaleOrderItem::query()->select('id')->where('sale_order_id', $saleOrderId))->exists()) {
            throw ValidationException::withMessages(['sale_order' => 'This Sale Order cannot be deleted after downstream Work Order history exists. Cancel it through the authorized operational process.']);
        }
    }

    public function assertCanChangeHeader(SaleOrder $order, array $fields): void
    {
        $hasDownstream = WorkOrderItem::query()->whereIn('sale_order_item_id', $order->saleOrderItems()->select('id'))->exists();
        if ($hasDownstream && array_intersect($fields, ['customer_id', 'billing_id', 'shipping_id'])) {
            throw ValidationException::withMessages(['sale_order' => 'Customer and address ownership cannot be changed after downstream Work Order history exists.']);
        }
    }

    /** @return list<string> */
    public function changedHeaderFields(SaleOrder $order, Request $request): array
    {
        return collect(['customer_id', 'billing_id', 'shipping_id'])
            ->filter(fn (string $field): bool => $request->has($field) && (string) $request->input($field) !== (string) ($order->{$field} ?? ''))
            ->keys()->all();
    }
}
