<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberSeries extends Model
{
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
