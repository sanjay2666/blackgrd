<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagingType extends Model
{
    use HasFactory;

    protected $table = 'packaging_types';
    public $timestamps = false;
    protected $guarded = [];
}
