<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMillReturnItem extends Model
{
    use HasFactory;
	public $timestamps      = false;
	protected $guarded = [];
	
	
	public function StockMillReturn()
	{
		return $this->hasOne(StockMillReturn::class, 'id', 'stock_mill_return_id')->where('status', '=', '1');
	}
	public function Item()
	{
		return $this->hasOne(Item::class, 'item_id', 'item_id')->where('status', '=', '1');
	}
	 
	
	public function ItemType()
	{
		return $this->hasOne(ItemType::class, 'item_type_id', 'item_type_id')->where('status', '=', '1');
	}
	
}
