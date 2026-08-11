<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsnCode extends Model
{
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'hsn_codes';

    protected $primaryKey = 'hsn_code_id';

    public $timestamps = false;

    protected $guarded = [];

    public function gstRate(): BelongsTo
    {
        return $this->belongsTo(GstRate::class, 'gst_rate_id', 'gst_rate_id');
    }
}
