<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllPage extends Model
{
    use HasFactory;

    protected $table = 'all_pages';
    public $timestamps = false;
    protected $guarded = [];
}
