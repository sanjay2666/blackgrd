<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowVersion extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'workflow_versions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
        'effective_from' => 'date',
        'published_at' => 'datetime',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowVersionStep::class, 'workflow_version_id')->orderBy('sequence');
    }

    public function saleOrderItems(): HasMany
    {
        return $this->hasMany(SaleOrderItem::class, 'workflow_version_id');
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query
            ->where('status', 'Published')
            ->whereHas('definition', fn (Builder $definition) => $definition->where('status', 'Active'));
    }
}
