<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkInspectionDetail extends Model
{
	use HasFactory;
	protected $table   = 'work_inspection_details';
	public $timestamps = false;
	protected $guarded = [];
	
	public function setInsItemIdAttribute($value): void
	{
		$this->attributes['item_id'] = $value;
	}

	public function setInsWorkOrderIdAttribute($value): void
	{
		$this->attributes['work_order_id'] = $value;
	}

	public function setOutputQuanSizeAttribute($value): void
	{
		$this->attributes['output_quantity'] = $value;
	}

	public function setShrinkageQuanSizeAttribute($value): void
	{
		$this->attributes['shrinkage_quantity'] = $value;
	}

	public function setInspecCommentAttribute($value): void
	{
		$this->attributes['inspection_comment'] = $value;
	}

	public function setCreatedAttribute($value): void
	{
		$this->attributes['created_at'] = $value;
	}

	public function setStatusAttribute($value): void
	{
		$this->attributes['status'] = in_array($value, [1, '1'], true) ? 'Active' : $value;
	}

	
	 
	public function FabricFaultReason()
    {
        return $this->hasOne(FabricFaultReason::class, 'id', 'fabric_fault_reason_id');
    }
	 
}
