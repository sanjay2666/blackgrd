<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'warehouses';

    public $timestamps = false;

    protected $guarded = [];
}
