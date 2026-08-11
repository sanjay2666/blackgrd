<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemYarnRequirement extends Model
{
    use HasFactory;

    protected $table = 'item_yarn_requirements';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function yarnItem()
    {
        return $this->belongsTo(Item::class, 'yarn_id', 'item_id');
    }

    public function process()
    {
        return $this->belongsTo(ProcessItem::class, 'process_id');
    }

    public function getyarn()
    {
        return $this->yarnItem();
    }
}
