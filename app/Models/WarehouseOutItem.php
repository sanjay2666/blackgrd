<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseOutItem extends Model
{
    use HasFactory;
	
	protected $table   = 'warehouse_out_items';
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
	
	
	
	public function DepartmentReturnRequest()
    {
        return $this->hasOne(DepartmentReturnRequest::class, 'ware_out_item_id', 'id');
    } 
	
	public function WarehouseItem()
    {
        return $this->hasOne(WarehouseItem::class, 'id', 'warehouse_item_id');
    } 
	
	 
	
	public function WarehouseItemStock()
	{
		return $this->hasOne(WarehouseItemStock::class, 'id', 'wis_id');
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
	public function Individual()
	{
		return $this->hasOne(Individual::class, 'id', 'ind_emp_id');
	} 
	public function Item()
    {
        return $this->hasOne(Item::class, 'item_id', 'item_id');
    }
	public function ItemType()
    {
        return $this->hasOne(ItemType::class,'item_type_id','item_type_id');
    }
	public function UnitType()
    {
        return $this->hasOne(UnitType::class,'unit_type_id','unit_type_id');
    }
	
	
	
	
	
	
}
