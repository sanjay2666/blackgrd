<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessItemAllowedNext extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'process_item_allowed_next';

    protected $guarded = [];

    public function process(): BelongsTo
    {
        return $this->belongsTo(ProcessItem::class, 'process_item_id');
    }

    public function nextProcess(): BelongsTo
    {
        return $this->belongsTo(ProcessItem::class, 'next_process_item_id');
    }
}
