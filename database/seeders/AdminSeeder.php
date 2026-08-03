<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public const DEFAULT_ADMIN_EMAIL = 'admin@blackgrd.test';
    public const DEFAULT_ADMIN_PASSWORD = 'Admin@12345';

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => self::DEFAULT_ADMIN_EMAIL],
            [
                'user_type' => 'Admin',
                'individual_id' => null,
                'name' => 'Default Admin',
                'password' => Hash::make(self::DEFAULT_ADMIN_PASSWORD),
                'status' => 'Active',
            ]
        );
    }
}