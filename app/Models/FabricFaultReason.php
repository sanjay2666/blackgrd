<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricFaultReason extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'fabric_fault_reasons';

    public $timestamps = false;

    protected $guarded = [];

    public function process(): BelongsTo
    {
        return $this->belongsTo(ProcessItem::class, 'process_id');
    }
}
