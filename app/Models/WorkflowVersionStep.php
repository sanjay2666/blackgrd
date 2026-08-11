<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class WorkflowVersionStep extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'workflow_version_steps';

    public $timestamps = false;

    protected $guarded = [];

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ProcessItem::class, 'process_id');
    }
}
