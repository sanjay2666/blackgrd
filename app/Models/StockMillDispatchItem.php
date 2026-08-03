<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMillDispatchItem extends Model
{
    use HasFactory;
	public $timestamps      = false;
	protected $guarded = [];
	
	
	public function StockMillDispatch()
	{
		return $this->hasOne(StockMillDispatch::class, 'id', 'stock_mill_dispatch_id')->where('status', '=', 'Active');
	}
	public function Item()
	{
		return $this->hasOne(Item::class, 'item_id', 'item_id')->where('status', '=', 'Active');
	}
	
	public function ReceiveStockMillDispatchItem()
	{
		return $this->hasMany(ReceiveStockMillDispatchItem::class, 'stock_mill_dispatch_item_id', 'id')->where('status', '=', 'Active');
	}
	
	
	
	public function ItemType()
	{
		return $this->hasOne(ItemType::class, 'item_type_id', 'item_type_id')->where('status', '=', 'Active');
	}
	
}
