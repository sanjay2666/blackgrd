<?php

namespace App\Models;

/**
 * Admin-panel view of the existing Admin-discriminated account record.
 * Authentication remains on the existing admin guard and provider contract.
 */
class Admin extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('admin-account', fn ($query) => $query->where('user_type', 'Admin'));
    }
}
