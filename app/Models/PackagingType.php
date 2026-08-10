<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagingType extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'packaging_types';

    public $timestamps = false;

    protected $guarded = [];
}
