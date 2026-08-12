<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function configuration(): HasOne
    {
        return $this->hasOne(ProcessItemConfiguration::class, 'process_item_id');
    }

    public function materialConfigurations(): HasMany
    {
        return $this->hasMany(ProcessItemMaterialConfiguration::class, 'process_item_id');
    }

    public function allowedNextProcesses(): HasMany
    {
        return $this->hasMany(ProcessItemAllowedNext::class, 'process_item_id');
    }
}
