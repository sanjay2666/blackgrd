<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    use HasFactory;

    protected $table = 'unit_type';
    protected $primaryKey = 'unit_type_id';
    public $timestamps = false;
    protected $guarded = [];
}
