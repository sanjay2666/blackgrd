<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesChallanRollAllocation extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = ['packed_quantity_snapshot' => 'decimal:2', 'previously_dispatched_quantity_snapshot' => 'decimal:2', 'dispatched_quantity' => 'decimal:2'];

    public function salesChallan(): BelongsTo
    {
        return $this->belongsTo(SalesChallan::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function salesChallanItem(): BelongsTo
    {
        return $this->belongsTo(SalesChallanItem::class);
    }

    public function packagingRollAllocation(): BelongsTo
    {
        return $this->belongsTo(PackagingRollAllocation::class);
    }
}
