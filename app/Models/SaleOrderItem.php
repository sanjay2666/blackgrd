<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleOrderItem extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'sale_order_items';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function getSaleOrderItemIdAttribute()
    {
        return $this->attributes['id'] ?? null;
    }

    public function getNameAttribute()
    {
        return $this->attributes['item_name'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['item_name'] = $value;
    }

    public function getCoatedPvcAttribute()
    {
        return $this->attributes['coating_type'] ?? null;
    }

    public function setCoatedPvcAttribute($value): void
    {
        $this->attributes['coating_type'] = $value;
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'item_type_id', 'item_type_id');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'unit_type_id');
    }

    public function CwoReason(): HasMany
    {
        return $this->hasMany(Reason::class, 'sale_order_item_id', 'id')
            ->where('reason_from_page', 'cwo')
            ->where('status', 'Active')
            ->orderByDesc('created_at');
    }

    public function WorkOrderItem(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class, 'sale_order_item_id', 'id')
            ->where('status', 'Active');
    }
}
