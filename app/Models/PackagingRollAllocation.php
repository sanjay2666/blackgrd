<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagingRollAllocation extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $guarded = [];

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
}
