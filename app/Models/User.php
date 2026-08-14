<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use EncryptsRouteKey;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'user_type',
        'individual_id',
        'name',
        'email',
        'password',
        'financial_year',
        'created_by',
        'modified_by',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizationAccess(): HasMany
    {
        return $this->hasMany(UserOrganizationAccess::class);
    }

    public function departmentAccess(): HasMany
    {
        return $this->hasMany(UserDepartmentAccess::class);
    }

    public function individual(): BelongsTo
    {
        return $this->belongsTo(Individual::class, 'individual_id');
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class, 'principal_id')
            ->where('principal_type', $this->user_type);
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }
}
