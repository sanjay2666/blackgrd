<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DyeingColour extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'dyeing_colours';

    public $timestamps = false;

    protected $guarded = [];

    public function colour(): BelongsTo
    {
        return $this->belongsTo(Colour::class, 'colour_id');
    }
}
