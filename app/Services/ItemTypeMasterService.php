<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\ItemType;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use LogicException;

final class ItemTypeMasterService
{
    /** IDs whose identity is embedded in operational code and historical data. */
    public const PROTECTED_IDENTITIES = [
        1 => 'Yarn', 2 => 'Beam', 3 => 'Greige', 4 => 'Dyed',
        5 => 'Coated', 8 => 'Fabric',
    ];

    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'items', 'sale_order_items', 'work_orders', 'work_order_items',
        'work_process_requirements', 'work_purchase_requirements',
        'work_inspection_details', 'warehouse_in_items', 'warehouse_out_items',
        'warehouse_balance_items', 'warehouse_item_stocks', 'purchase_items',
        'purchase_order_items', 'stock_mill_dispatch_items',
        'receive_stock_mill_dispatch_items', 'stock_mill_return_items', 'gate_passes',
    ];

    public function __construct(private readonly DatabaseManager $database)
    {
    }

    public function assertMutable(ItemType $itemType, array $attributes): void
    {
        $id = (int) $itemType->getKey();
        if (isset(self::PROTECTED_IDENTITIES[$id])) {
            foreach (['item_type_name', 'short_code'] as $field) {
                if (array_key_exists($field, $attributes) && $attributes[$field] !== null
                    && strcasecmp(trim((string) $attributes[$field]), self::PROTECTED_IDENTITIES[$id]) !== 0
                    && ! ($field === 'short_code' && strtoupper(trim((string) $attributes[$field])) === self::codeForId($id))) {
                    throw new LogicException('The identity of this core Item Type is protected.');
                }
            }
            if (($attributes['status'] ?? null) === RecordStatus::Inactive->value) {
                throw new LogicException('Core Item Types cannot be deactivated because ERP flows depend on them.');
            }
        }
    }

    public function isReferenced(ItemType $itemType): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'item_type_id')
                && $this->database->table($table)->where('item_type_id', $itemType->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    public static function codeForId(int $id): string
    {
        return match ($id) {
            1 => 'YARN', 2 => 'BEAM', 3 => 'GREIGE', 4 => 'DYED', 5 => 'COATED',
            6 => 'GENERAL', 7 => 'CHEMICAL', 8 => 'FABRIC', 9 => 'COLOUR', default => 'TYPE'.$id,
        };
    }
}
