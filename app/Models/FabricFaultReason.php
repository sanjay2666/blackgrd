<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricFaultReason extends Model
{
    use HasFactory;
	protected $table   = 'fabric_fault_reasons';
	public $timestamps = false;
	protected $guarded = [];
}
