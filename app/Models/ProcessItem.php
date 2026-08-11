<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessItem extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'process_items';

    public $timestamps = false;

    protected $guarded = [];

    public function individuals(): HasMany
    {
        return $this->hasMany(Individual::class, 'process_type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
