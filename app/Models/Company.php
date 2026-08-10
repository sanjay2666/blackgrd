<?php

namespace App\Models;

use App\Models\Concerns\HasRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    use HasRecordStatus;

    protected $table = 'companies';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeCanonical(Builder $query): Builder
    {
        return $query->active()->orderBy($this->qualifyColumn('id'));
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function factories(): HasMany
    {
        return $this->hasMany(Factory::class);
    }

    public function organizationAccess(): HasMany
    {
        return $this->hasMany(UserOrganizationAccess::class);
    }
}
