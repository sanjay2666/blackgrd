<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualAddress extends Model
{
    use HasFactory;

    protected $table = 'individual_address';
    protected $primaryKey = 'ind_add_id';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_address' => 'boolean',
        ];
    }

    public function individual(): BelongsTo
    {
        return $this->belongsTo(Individual::class, 'individual_id');
    }
}
