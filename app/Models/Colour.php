<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colour extends Model
{
    use HasFactory, HasRecordStatus, BelongsToCompany;

    protected $table = 'colours';

    public $timestamps = false;

    protected $guarded = [];
}
