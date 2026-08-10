<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coting extends Model
{
    use BelongsToCompany, HasFactory, HasRecordStatus;

    protected $table = 'cotings';

    public $timestamps = false;

    protected $guarded = [];
}
