<?php

namespace App\Models;

use App\Enums\PurchaseOrderDocumentStatus;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'purchase_orders';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'purchased_on' => 'datetime',
        'purchase_started' => 'datetime',
        'document_status' => PurchaseOrderDocumentStatus::class,
    ];

    public function scopeWithDocumentStatus(Builder $query, PurchaseOrderDocumentStatus|string $status): Builder
    {
        return $query->where('document_status', $status instanceof PurchaseOrderDocumentStatus ? $status->value : $status);
    }

    public function PurchaseOrderItem()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_id', 'id')
            ->where('status', '!=', 'Deleted')
            ->where('is_deleted', false);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'purchase_order_id', 'id')
            ->where('status', 'Active');
    }

    public function Individual()
    {
        return $this->vendor();
    }

    public function vendor()
    {
        return $this->belongsTo(Individual::class, 'vendor_id', 'id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(IndividualAddress::class, 'billing_id', 'ind_add_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(IndividualAddress::class, 'shiping_id', 'ind_add_id');
    }
}
