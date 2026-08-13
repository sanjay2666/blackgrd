<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagingOrder extends Model
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Individual::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackagingOrderItem::class)->where('status', 'Active');
    }

    public function rollAllocations(): HasMany
    {
        return $this->hasMany(PackagingRollAllocation::class)->where('status', 'Active');
    }
}
