<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coting extends Model
{
    use HasFactory;

    protected $table = 'cotings';
    public $timestamps = false;
    protected $guarded = [];
}
