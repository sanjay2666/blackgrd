<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{
	use HasFactory; 
	protected $table  = 'gate_passes';	
	public $timestamps = false;
	protected $guarded = [];
	
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
	
	public function WorkInspection()
	{
		return $this->hasOne(WorkInspection::class, 'id', 'inspection_id')->where('status', '!=', 'Deleted')->where('is_deleted', '=', '0');
	}
	
	 
}
