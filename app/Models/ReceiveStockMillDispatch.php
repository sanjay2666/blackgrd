<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiveStockMillDispatch extends Model
{
	use HasFactory;
	public $timestamps      = false;
	protected $guarded = [];
	
	
	
	
	public function ReceiveStockMillDispatchItem()
	{
		return $this->hasMany(ReceiveStockMillDispatchItem::class, 'receive_mill_dispatch_id', 'id')->where('status', '=', 'Active');
	}	
	
}
