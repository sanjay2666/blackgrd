<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use HasFactory;
    protected $table   		= 'work_orders';
    protected $primaryKey   = 'id';
	public $timestamps = false;
	protected $guarded = [];

	public function getWorkOrderIdAttribute()
	{
		return $this->attributes['id'] ?? null;
	}

	public function getChildWorkOrderIdAttribute()
	{
		return $this->attributes['parent_work_order_id'] ?? null;
	}

	public function getCreatedAttribute()
	{
		return $this->attributes['created_at'] ?? null;
	}

	public function setChildWorkOrderIdAttribute($value): void
	{
		$this->attributes['parent_work_order_id'] = $value;
	}

	public function setCreatedAttribute($value): void
	{
		$this->attributes['created_at'] = $value;
	}

	public function setModifiedAttribute($value): void
	{
		$this->attributes['modified_at'] = $value;
	}

	public function setStatusAttribute($value): void
	{
		$this->attributes['status'] = in_array($value, [1, '1'], true) ? 'Active' : $value;
	}
	
	
	
	public function WorkMaster()
	{
		return $this->hasOne(Individual::class, 'id', 'master_ind_id');
	}
	public function WorkProcessRequirement(){
		return $this->hasMany(WorkProcessRequirement::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted')->where('is_accept', '!=', '2');
	}
	
	public function WorkMachine()
	{
		return $this->hasOne(Machine::class, 'id', 'machine_id');
	}
	
	public function WorkReqSend()
	{
		return $this->hasOne(Individual::class, 'id', 'work_req_send_by');
	}	
	public function ProcessType()
	{
		return $this->hasOne(ProcessItem::class, 'id', 'process_type_id')->where('status', '!=', 'Deleted');
	} 
	
	public function Item()
	{
		return $this->hasOne(Item::class, 'item_id', 'item_id');
	}
	
	public function GatepassGenratedByWarehouseUser()
	{
		return $this->hasOne(Individual::class, 'id', 'gatepass_genrated_by_warehouse_user')->where('status', '!=', 'Deleted')->select('id', 'name');
	}
	
	
	public function WarehouseItem()
	{
		return $this->hasOne(WarehouseItem::class, 'process_type_id', 'process_type_id');
	}
	
	public function WarehouseOutItem()
	{
		return $this->hasMany(WarehouseOutItem::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted');
	}	
	
	public function WorkOrderItem(){
		return $this->hasMany(WorkOrderItem::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted');
	}
	
	
	
	public function DepartmentReturnRequest(){
		return $this->hasMany(DepartmentReturnRequest::class, 'work_order_id', 'id')->where('status', '=', 'accepted');
	}
	
	
	public function GatePass(){
		return $this->hasMany(GatePass::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted');
	}
	
	 
	public function WorkInspectionOne()
	{
		return $this->hasOne(WorkInspection::class, 'id', 'inspection_id')->where('status', '!=', 'Deleted');
	}
	
	public function WorkInspection(){
		return $this->hasMany(WorkInspection::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted');
	}
	
	
	public function workOrderItemSingle()
	{
		return $this->hasOne(WorkOrderItem::class, 'work_order_id', 'id');
	}
	 
	
	
	 
	
	 

}




