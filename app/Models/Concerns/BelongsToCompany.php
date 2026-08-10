<?php

namespace App\Models\Concerns;

use App\Services\CurrentOrganizationContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('currentCompany', function (Builder $query): void {
            $context = request()->attributes->get(CurrentOrganizationContext::class);
            if ($context instanceof CurrentOrganizationContext) {
                $query->where($query->getModel()->getTable().'.company_id', $context->companyId());
            }
        });

        static::creating(function ($model): void {
            $context = request()->attributes->get(CurrentOrganizationContext::class);
            if ($context instanceof CurrentOrganizationContext) {
                $model->company_id = $context->companyId();
            }
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where($query->getModel()->getTable().'.company_id', $companyId);
    }
}
