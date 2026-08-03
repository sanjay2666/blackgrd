<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkInspection extends Model
{
    use HasFactory;

    protected $table = 'work_inspections';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function getCreatedAttribute()
    {
        return $this->attributes['created'] ?? $this->attributes['created_at'] ?? null;
    }

    public function setInsItemIdAttribute($value): void
    {
        $this->attributes['item_id'] = $value;
    }

    public function setCreatedAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = in_array($value, [1, '1'], true) ? 'Active' : $value;
    }

    public function WorkOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id')->where('status', '!=', 'Deleted');
    }
}
