<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItemStockFile extends Model
{
    use HasFactory;

    protected $table = 'warehouse_item_stock_files';
    public $timestamps = false;
    protected $guarded = [];

    public function setCreatedAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }

    public function setModifiedAttribute($value): void
    {
        $this->attributes['updated_at'] = $value;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = in_array($value, [1, '1'], true) ? 'Active' : $value;
    }

    public function stock()
    {
        return $this->belongsTo(WarehouseItemStock::class, 'wis_id', 'id');
    }
}
