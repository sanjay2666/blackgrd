<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'workflow_definitions';

    public $timestamps = false;

    protected $guarded = [];

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class, 'workflow_definition_id')->orderByDesc('version_number');
    }

    public function latestVersion(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class, 'workflow_definition_id')->orderByDesc('version_number')->limit(1);
    }
}
