<?php

namespace App\Models\Concerns;

use App\Casts\RecordStatusCast;
use App\Enums\RecordStatus;
use App\Support\RecordStatusTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasRecordStatus
{
    public function initializeHasRecordStatus(): void
    {
        $this->mergeCasts(['status' => RecordStatusCast::class]);
    }

    public static function bootHasRecordStatus(): void
    {
        static::updating(function (Model $model): void {
            if (! $model->isDirty('status')) {
                return;
            }

            RecordStatusTransition::ensureAllowed(
                $model->getRawOriginal('status'),
                $model->getAttribute('status'),
            );
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), RecordStatus::Active->value);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), RecordStatus::Inactive->value);
    }

    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), '!=', RecordStatus::Deleted->value);
    }
}
