<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentReturn extends Model
{
    use HasFactory;
	
	protected $table   = 'department_returns';
	public $timestamps = false;
	protected $guarded = [];
	
	
	
	public function Individual(){
		return $this->hasOne(Individual::class, 'id', 'employee_id');
	}
	
	public function DepartmentReturnRequest()  
    {
        return $this->hasMany(DepartmentReturnRequest::class, 'depart_reqst_id', 'id')->where('status', '=', 'pending');
    }
	
	public function AcceptedDepartmentReturnRequest()  
    {
        return $this->hasMany(DepartmentReturnRequest::class, 'depart_reqst_id', 'id')->where('status', '=', 'accepted');
    }
	
}
