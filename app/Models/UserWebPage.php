<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWebPage extends Model
{
    use HasFactory;

    protected $table = 'user_web_pages';
    public $timestamps = false;
    protected $guarded = [];
}
