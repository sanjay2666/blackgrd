<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesChallan extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = ['challan_date' => 'date', 'dispatch_date' => 'date', 'lr_date' => 'date', 'total_meter' => 'decimal:2', 'posted_at' => 'datetime', 'cancelled_at' => 'datetime', 'first_printed_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Individual::class, 'customer_id');
    }
    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Individual::class, 'transporter_id');
    }
    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(SalesChallanItem::class)->where('record_status', 'Active');
    }
    public function rollAllocations(): HasMany
    {
        return $this->hasMany(SalesChallanRollAllocation::class)->where('record_status', 'Active');
    }
}
