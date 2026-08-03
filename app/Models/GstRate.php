<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GstRate extends Model
{
    use HasFactory;

    protected $table = 'gst_rates';
    protected $primaryKey = 'gst_rate_id';
    public $timestamps = false;
    protected $guarded = [];
}
