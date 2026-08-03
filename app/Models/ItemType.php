<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemType extends Model
{
    use HasFactory, HasRecordStatus;

    protected $table = 'item_type';

    protected $primaryKey = 'item_type_id';

    public $timestamps = false;

    protected $guarded = [];

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'unit_type_id');
    }
}
