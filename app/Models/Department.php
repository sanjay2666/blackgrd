<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'departments';

    public $timestamps = false;

    protected $guarded = [];
}
