<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $guarded = [];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withPivot('assigned_by')->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class);
    }

    public function isSystem(): bool
    {
        return $this->scope === 'System';
    }

    public function isForPanel(string $panel): bool
    {
        return $this->scope === 'System' || $this->panel === $panel;
    }
}
