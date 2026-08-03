<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    use HasFactory;

    protected $table = 'warehouse_in_items';
    public $timestamps = false;
    protected $guarded = [];

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

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = in_array($value, [1, '1'], true) ? 'Active' : $value;
    }

    public function Item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function ItemType()
    {
        return $this->belongsTo(ItemType::class, 'item_type_id', 'item_type_id');
    }

    public function UnitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'unit_type_id');
    }

    public function Vendor()
    {
        return $this->belongsTo(Individual::class, 'vendor_id', 'id');
    }

    public function Warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function WarehouseCompartment()
    {
        return $this->belongsTo(WarehouseCompartment::class, 'ware_comp_id', 'id');
    }

    public function ReceiverIndividual()
    {
        return $this->belongsTo(Individual::class, 'receiver_id', 'id');
    }
}
