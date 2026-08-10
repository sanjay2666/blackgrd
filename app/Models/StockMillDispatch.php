<?php

namespace App\Models;

use App\Enums\JobWorkStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMillDispatch extends Model
{
    use HasFactory, HasRecordStatus;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'job_work_status' => JobWorkStatus::class,
    ];

    public function scopeWithJobWorkStatus(Builder $query, JobWorkStatus|string $status): Builder
    {
        return $query->where('job_work_status', $status instanceof JobWorkStatus ? $status->value : $status);
    }

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

    public function StockMillDispatchItem()
    {
        return $this->hasMany(StockMillDispatchItem::class, 'stock_mill_dispatch_id', 'id')->where('status', '=', 'Active');
    }
}
