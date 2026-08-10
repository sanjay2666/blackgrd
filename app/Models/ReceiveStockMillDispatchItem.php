<?php

namespace App\Models;

use App\Enums\InventoryReceiptStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiveStockMillDispatchItem extends Model
{
    use HasFactory, HasRecordStatus;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'receipt_status' => InventoryReceiptStatus::class,
    ];

    public function dispatch()
    {
        return $this->belongsTo(StockMillDispatch::class, 'stock_mill_dispatch_id');
    }

    public function dispatchItem()
    {
        return $this->belongsTo(StockMillDispatchItem::class, 'stock_mill_dispatch_item_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }
}
