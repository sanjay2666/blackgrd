<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMillReturn extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    public function Vendor()
    {
        return $this->hasOne(Individual::class, 'id', 'vendor_id')->where('status', '=', 'Active');
    }

    public function ProcessType()
    {
        return $this->hasOne(ProcessItem::class, 'id', 'process_type')->where('status', '=', 'Active');
    }

    public function Item()
    {
        return $this->hasOne(Item::class, 'item_id', 'item_id')->where('status', '=', 'Active');
    }

    public function StockMillReturnItem()
    {
        return $this->hasMany(StockMillReturnItem::class, 'stock_mill_return_id', 'id')->where('status', '=', 'Active');
    }
}
