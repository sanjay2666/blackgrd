<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesChallanItem extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = ['dispatched_quantity' => 'decimal:2'];

    public function salesChallan(): BelongsTo
    {
        return $this->belongsTo(SalesChallan::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function packagingOrderItem(): BelongsTo
    {
        return $this->belongsTo(PackagingOrderItem::class);
    }

    public function rollAllocations(): HasMany
    {
        return $this->hasMany(SalesChallanRollAllocation::class)->where('record_status', 'Active');
    }
}
