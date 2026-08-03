<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessItem extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'process_items';

    public $timestamps = false;

    protected $guarded = [];

    public function individuals(): HasMany
    {
        return $this->hasMany(Individual::class, 'process_type_id');
    }
}
