<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkProcessRequirement extends Model
{
    use HasFactory;

    protected $table = 'work_process_requirements';
    public $timestamps = false;
    protected $guarded = [];

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

    public function WorkOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id');
    }

    public function WorkOrderItem()
    {
        return $this->belongsTo(WorkOrderItem::class, 'work_order_item_id', 'id');
    }

    public function CreatedBy()
    {
        return $this->belongsTo(Individual::class, 'created_by', 'id');
    }

    public function ModifiedBy()
    {
        return $this->belongsTo(Individual::class, 'modified_by', 'id');
    }

    public function WarehouseItemStock()
    {
        return $this->hasMany(WarehouseItemStock::class, 'work_pro_req_id', 'id')->where('status', 'Active');
    }

    public function getQuantityAttribute()
    {
        return $this->attributes['quantity'] ?? $this->attributes['required_quantity'] ?? 0;
    }

    public function getAllotedQuantityAttribute()
    {
        return $this->attributes['alloted_quantity'] ?? $this->attributes['issued_quantity'] ?? 0;
    }

    public function getRequiredQuantityAttribute()
    {
        return $this->attributes['quantity'] ?? $this->attributes['required_quantity'] ?? 0;
    }

    public function getIssuedQuantityAttribute()
    {
        return $this->attributes['alloted_quantity'] ?? $this->attributes['issued_quantity'] ?? 0;
    }

    public function getBalanceQuantityAttribute()
    {
        $quantity = (float) ($this->attributes['quantity'] ?? $this->attributes['required_quantity'] ?? 0);
        $allotedQuantity = (float) ($this->attributes['alloted_quantity'] ?? $this->attributes['issued_quantity'] ?? 0);

        return max(0, $quantity - $allotedQuantity);
    }

    public function getCreatedAttribute()
    {
        return $this->attributes['created_at'] ?? null;
    }

    public function getWorkReqSendByAttribute()
    {
        return $this->attributes['created_by'] ?? null;
    }

    public function getProcessAcceptedByAttribute()
    {
        return $this->attributes['modified_by'] ?? null;
    }

    public function getProcessDenyByAttribute()
    {
        return $this->attributes['modified_by'] ?? null;
    }

    public function getIsProAccByWarehouseAttribute()
    {
        return match ((int) ($this->attributes['is_accept'] ?? 0)) {
            1 => 'Yes',
            2 => 'No',
            default => null,
        };
    }

    public function getCoatedPvcAttribute()
    {
        return $this->attributes['coating_type'] ?? null;
    }

    public function getReqLotNoAttribute()
    {
        return $this->attributes['req_lot_no'] ?? null;
    }

    public function getReqFabricTypeAttribute()
    {
        return $this->attributes['req_fabric_type'] ?? 1;
    }

    public function getWisIdAttribute()
    {
        return $this->attributes['wis_id'] ?? null;
    }

    public function getWarehouseBalanceItemIdAttribute()
    {
        return $this->attributes['warehouse_balance_item_id'] ?? null;
    }

    public function getIsAllItemReturnedAttribute()
    {
        return $this->attributes['is_all_item_returned'] ?? 'No';
    }
}
