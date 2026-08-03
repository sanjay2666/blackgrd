<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'machines';

    public $timestamps = false;

    protected $guarded = [];

    public function processItem(): BelongsTo
    {
        return $this->belongsTo(ProcessItem::class, 'process_wise');
    }
}
