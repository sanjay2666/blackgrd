<?php

namespace App\Models;

use App\Enums\InventoryMovementStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseBalanceItem extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'warehouse_balance_items';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'movement_status' => InventoryMovementStatus::class,
    ];

    protected $primaryKey = 'id';

    public function getCoatedPvcAttribute()
    {
        return $this->attributes['coating_type'] ?? null;
    }

    public function setCoatedPvcAttribute($value): void
    {
        $this->attributes['coating_type'] = $value;
    }

    public function getCreatedAttribute()
    {
        return $this->attributes['created_at'] ?? null;
    }

    public function setCreatedAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }

    public function setModifiedAttribute($value): void
    {
        $this->attributes['updated_at'] = $value;
    }

    public function WarehouseItem()
    {
        return $this->belongsTo(WarehouseItem::class, 'ware_in_item_id', 'id');
    }

    public function WarehouseOutItem()
    {
        return $this->belongsTo(WarehouseOutItem::class, 'ware_out_item_id', 'id');
    }

    public function Purchase()
    {
        return $this->hasOne(Purchase::class, 'id', 'purchase_id');
    }

    public function Warehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }

    public function WarehouseCompartment()
    {
        return $this->hasOne(WarehouseCompartment::class, 'id', 'ware_comp_id');
    }

    public function User()
    {
        return $this->hasOne(User::class, 'id', 'receiver_id');
    }

    public function ReceiverIndividual(): BelongsTo
    {
        return $this->belongsTo(Individual::class, 'receiver_id', 'id');
    }

    public function ItemType()
    {
        return $this->hasOne(ItemType::class, 'item_type_id', 'item_type_id');
    }

    public function UnitType()
    {
        return $this->hasOne(UnitType::class, 'unit_type_id', 'unit_type_id');
    }

    public function Item()
    {
        return $this->hasOne(Item::class, 'item_id', 'item_id');
    }
}
