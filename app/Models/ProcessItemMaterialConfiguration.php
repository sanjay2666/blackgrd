<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessItemMaterialConfiguration extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'process_item_material_configurations';

    protected $guarded = [];

    public function process(): BelongsTo
    {
        return $this->belongsTo(ProcessItem::class, 'process_item_id');
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class, 'item_type_id', 'item_type_id');
    }
}
