<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentReturnRequest extends Model
{
    use HasFactory;
	
	protected $table   = 'department_return_requests';
	public $timestamps = false;
	protected $guarded = [];
	
	
	public function Item()
	{
		return $this->hasOne(Item::class, 'item_id', 'item_id');
	}
	
	 
	
	
	
	
}
