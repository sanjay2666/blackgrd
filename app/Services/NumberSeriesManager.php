<?php

namespace App\Services;

use App\Models\NumberSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class NumberSeriesManager
{
    public function update(NumberSeries $series, array $attributes): NumberSeries
    {
        if (array_key_exists('next_number', $attributes) && (int) $attributes['next_number'] < (int) $series->next_number) {
            throw ValidationException::withMessages(['next_number' => 'The next number cannot be lowered below the already-issued range.']);
        }

        return DB::transaction(function () use ($series, $attributes): NumberSeries {
            $locked = NumberSeries::query()->whereKey($series->id)->lockForUpdate()->firstOrFail();
            if ((int) ($attributes['next_number'] ?? $locked->next_number) < (int) $locked->next_number) {
                throw ValidationException::withMessages(['next_number' => 'The next number cannot be lowered below the already-issued range.']);
            }
            $old = $locked->only(['prefix', 'suffix', 'padding', 'next_number', 'reset_policy', 'financial_year_aware', 'status']);
            $locked->fill($attributes);
            $locked->modified_by = auth('admin')->id();
            $locked->save();
            app(AuditLogger::class)->recordAfterCommit([
                'module' => 'number-series', 'action' => 'manage', 'event' => 'number_series_configuration_changed',
                'auditable_type' => $locked->getMorphClass(), 'auditable_id' => $locked->id,
                'description' => 'Number series configuration changed.', 'old_values' => $old,
                'new_values' => $locked->only(array_keys($old)),
            ]);

            return $locked;
        });
    }
}
