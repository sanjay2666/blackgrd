<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberSeries extends Model
{
    use EncryptsRouteKey;

    protected $guarded = [];

    protected $casts = [
        'next_number' => 'integer',
        'padding' => 'integer',
        'financial_year_aware' => 'boolean',
    ];

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
