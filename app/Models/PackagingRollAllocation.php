<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagingRollAllocation extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $guarded = [];

    protected $casts = [
        'source_available_quantity' => 'decimal:2',
        'allocated_quantity' => 'decimal:2',
        'accepted_quantity' => 'decimal:2',
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

    public function packagingOrderItem(): BelongsTo
    {
        return $this->belongsTo(PackagingOrderItem::class);
    }

    public function warehouseItemStock(): BelongsTo
    {
        return $this->belongsTo(WarehouseItemStock::class);
    }

    public function warehouseOutItem(): BelongsTo
    {
        return $this->belongsTo(WarehouseOutItem::class);
    }

    public function salesChallanRollAllocations(): HasMany
    {
        return $this->hasMany(SalesChallanRollAllocation::class)->where('record_status', 'Active');
    }
}
