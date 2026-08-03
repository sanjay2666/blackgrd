<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkPurchaseRequirement extends Model
{
    use HasFactory;

    protected $table = 'work_purchase_requirements';
    public $timestamps = false;
    protected $guarded = [];

    public function WorkOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id');
    }

    public function WorkOrderItem()
    {
        return $this->belongsTo(WorkOrderItem::class, 'work_order_item_id', 'id');
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

    public function CreatedBy()
    {
        return $this->belongsTo(Individual::class, 'created_by', 'id');
    }

    public function ModifiedBy()
    {
        return $this->belongsTo(Individual::class, 'modified_by', 'id');
    }
}
