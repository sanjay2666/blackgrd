<?php

namespace App\Models;

use App\Enums\SaleOrderDocumentStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'sale_orders';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'document_status' => SaleOrderDocumentStatus::class,
    ];

    public function scopeWithDocumentStatus(Builder $query, SaleOrderDocumentStatus|string $status): Builder
    {
        return $query->where('document_status', $status instanceof SaleOrderDocumentStatus ? $status->value : $status);
    }

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
