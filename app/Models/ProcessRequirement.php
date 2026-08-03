<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessRequirement extends Model
{ 
	use HasFactory;
	protected $table   = 'process_requirements';
	public $timestamps = false;
	protected $guarded = [];
}
