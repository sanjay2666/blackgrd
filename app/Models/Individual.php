<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Individual extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'individuals';

    public $timestamps = false;

    protected $guarded = [];

    public function addresses(): HasMany
    {
        return $this->hasMany(IndividualAddress::class, 'individual_id');
    }

    public function activeAddresses(): HasMany
    {
        return $this->hasMany(IndividualAddress::class, 'individual_id')->where('status', 'Active');
    }

    public function processItem(): BelongsTo
    {
        return $this->belongsTo(ProcessItem::class, 'process_type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function IndividualBillingAddress()
    {
        return $this->hasmany(IndividualAddress::class, 'individual_id', 'id')->where('address_type', '=', 'b');
    }

    public function IndividualShipingAddress()
    {
        return $this->hasmany(IndividualAddress::class, 'individual_id', 'id')->where('address_type', '=', 's');
    }
}
