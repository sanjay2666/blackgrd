<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionGenealogyLink extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'production_genealogy_links';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];
}
