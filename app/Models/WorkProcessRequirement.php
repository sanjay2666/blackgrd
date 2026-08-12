<?php

namespace App\Models;

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use App\Enums\InventoryAllocationStatus;
use App\Enums\WorkRequirementStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkProcessRequirement extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'work_process_requirements';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'requirement_status' => WorkRequirementStatus::class,
        'allocation_status' => InventoryAllocationStatus::class,
    ];

    public function scopeWithRequirementStatus(Builder $query, WorkRequirementStatus|string $status): Builder
    {
        return $query->where('requirement_status', $status instanceof WorkRequirementStatus ? $status->value : $status);
    }

    public function setIsAcceptAttribute($value): void
    {
        $decision = (int) $value;
        $this->attributes['is_accept'] = $decision;

        $mapper = app(LegacyOperationalStatusMapper::class);
        $required = (float) ($this->attributes['quantity'] ?? 0);
        $allotted = (float) ($this->attributes['alloted_quantity'] ?? 0);
        $this->attributes['requirement_status'] = $mapper->workRequirement($decision, $required, $allotted)->value;
        $this->attributes['allocation_status'] = $mapper->allocation($required, $allotted, $decision)->value;
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

    public function WorkOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id');
    }

    public function WorkOrderItem(): HasOne
    {
        return $this->hasOne(WorkOrderItem::class, 'work_order_id', 'work_order_id')
            ->where('status', 'Active');
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

    public function WarehouseOutItem(): HasMany
    {
        return $this->hasMany(WarehouseOutItem::class, 'work_pro_req_id', 'id')
            ->where('status', '!=', 'Deleted');
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
