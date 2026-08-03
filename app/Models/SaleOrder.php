<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{
    use HasFactory;

    protected $table = 'sale_orders';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function getSaleOrderIdAttribute()
    {
        return $this->attributes['id'] ?? null;
    }

    public function getIndividualIdAttribute()
    {
        return $this->attributes['customer_id'] ?? null;
    }

    public function setIndividualIdAttribute($value): void
    {
        $this->attributes['customer_id'] = $value;
    }

    public function customer()
    {
        return $this->belongsTo(Individual::class, 'customer_id', 'id');
    }

    public function Individual()
    {
        return $this->customer();
    }

    public function employee()
    {
        return $this->belongsTo(Individual::class, 'order_by_employee', 'id');
    }

    public function agent()
    {
        return $this->belongsTo(Individual::class, 'ind_agent_id', 'id');
    }

    public function saleOrderItems()
    {
        return $this->hasMany(SaleOrderItem::class, 'sale_order_id', 'id');
    }
}
