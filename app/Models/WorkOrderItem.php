<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderItem extends Model
{
    use HasFactory;
	 
	protected $table   		= 'work_order_items';
    protected $primaryKey 	= 'id';
	public $timestamps 		= false;
	protected $guarded = [];

	public function getWoiIdAttribute()
	{
		return $this->attributes['id'] ?? null;
	}

	public function getCoatedPvcAttribute()
	{
		return $this->attributes['coating_type'] ?? null;
	}

	public function setCoatedPvcAttribute($value): void
	{
		$this->attributes['coating_type'] = $value;
	}

	public function Customer()
    {
        return $this->belongsTo(Individual::class, 'customer_id', 'id')->where('status', '!=', 'Deleted');
    }
	
	public function WorkOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted');
    }
	 
	public function WarehouseOutItem(){
		return $this->hasMany(WarehouseOutItem::class, 'work_order_id', 'work_order_id')->where('status', '!=', 'Deleted');
	}	 
	 
	public function SaleOrder()
	{
		return $this->belongsTo(SaleOrder::class, 'sale_order_id', 'id')->where('status', '!=', 'Deleted');
	} 
	
	public function SaleOrderItem()
	{
		return $this->belongsTo(SaleOrderItem::class, 'sale_order_item_id', 'id')->where('status', '!=', 'Deleted');
	} 
	 
}
