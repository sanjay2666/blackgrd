<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricQuality extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'fabric_qualities';

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
