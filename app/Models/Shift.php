<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToCompany;
    use HasRecordStatus;

    protected $table = 'shifts';

    public $timestamps = false;

    protected $guarded = [];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function getDurationMinutesAttribute(): int
    {
        $start = $this->minutes($this->start_time);
        $end = $this->minutes($this->end_time);

        return ($end >= $start ? $end : $end + 1440) - $start;
    }

    private function minutes(?string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr((string) $time, 0, 5)));

        return ($hours * 60) + $minutes;
    }
}
