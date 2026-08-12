<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'states';

    public $timestamps = false;

    protected $guarded = [];
}
