<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GstRate extends Model
{
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'gst_rates';

    protected $primaryKey = 'gst_rate_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['gst_rate' => 'decimal:2'];
}
