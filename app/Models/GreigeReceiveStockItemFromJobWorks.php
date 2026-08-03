<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GreigeReceiveStockItemFromJobWorks extends Model
{
    use HasFactory;

    protected $table = 'greige_receive_stock_item_from_job_works';
    public $timestamps = false;
    protected $guarded = [];

    public function receiveMillDispatch()
    {
        return $this->belongsTo(ReceiveStockMillDispatch::class, 'greige_receive_id', 'id');
    }

    public function stockMillDispatchItem()
    {
        return $this->belongsTo(StockMillDispatchItem::class, 'stock_mill_dispatch_item_id', 'id');
    }

    public function receivedItem()
    {
        return $this->belongsTo(Item::class, 'received_item_id', 'item_id');
    }

    public function usedYarn()
    {
        return $this->belongsTo(Item::class, 'used_yarn_id', 'item_id');
    }

    public function usedBeam()
    {
        return $this->belongsTo(Item::class, 'used_beam_id', 'item_id');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'unit_type_id');
    }
}
