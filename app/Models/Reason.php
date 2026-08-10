<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    use BelongsToCompany, HasFactory;

    public $timestamps = false;

    protected $table = 'reasons';

    protected $guarded = [];
}
