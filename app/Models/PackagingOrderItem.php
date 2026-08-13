<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagingOrderItem extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $guarded = [];

    protected $casts = [
        'allocated_quantity' => 'decimal:2',
        'packed_quantity' => 'decimal:2',
        'dispatched_quantity' => 'decimal:2',
        'cancelled_quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
    ];

    public function packagingOrder(): BelongsTo
    {
        return $this->belongsTo(PackagingOrder::class);
    }

    public function saleOrderItem(): BelongsTo
    {
        return $this->belongsTo(SaleOrderItem::class);
    }

    public function packagingType(): BelongsTo
    {
        return $this->belongsTo(PackagingType::class);
    }

    public function rollAllocations(): HasMany
    {
        return $this->hasMany(PackagingRollAllocation::class)->where('status', 'Active');
    }
}
