<?php

namespace App\Models;

use App\Enums\InventoryReceiptStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiveStockMillDispatch extends Model
{
    use HasFactory, HasRecordStatus;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'receipt_status' => InventoryReceiptStatus::class,
    ];

    public function ReceiveStockMillDispatchItem()
    {
        return $this->hasMany(ReceiveStockMillDispatchItem::class, 'receive_mill_dispatch_id', 'id')->where('status', '=', 'Active');
    }
}
