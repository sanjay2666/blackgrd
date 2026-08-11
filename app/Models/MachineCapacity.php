<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineCapacity extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToCompany;
    use HasRecordStatus;

    protected $table = 'machine_capacities';

    public $timestamps = false;

    protected $guarded = [];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }
}
