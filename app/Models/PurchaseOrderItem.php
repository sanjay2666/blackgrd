<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';
	public $timestamps = false;
	protected $guarded = [];
	
	 
	public function PurchaseOrder()
	{
		return $this->belongsTo(PurchaseOrder::class, 'purchase_id', 'id')->where('status', 'Active')->where('is_deleted', 'No');
	} 
	
	
	public function Item()
	{
		return $this->belongsTo(Item::class, 'item_id', 'item_id')->where('status', 'Active')->select('item_id', 'item_name', 'unit_type_id', 'item_type_id');
	}
	 
	
	public function ItemType()
	{
		return $this->belongsTo(ItemType::class, 'item_type_id', 'item_type_id')->select('item_type_id', 'item_type_name');
	}

	public function purchaseItems()
	{
		return $this->hasMany(PurchaseItem::class, 'purchase_order_item_id', 'id')
			->where('status', 'Active');
	}
}
