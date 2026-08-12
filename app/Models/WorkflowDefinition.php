<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function currentVersion(): HasOne
    {
        return $this->hasOne(WorkflowVersion::class, 'workflow_definition_id')
            ->where('status', 'Published')
            ->where('is_current', true);
    }
}
