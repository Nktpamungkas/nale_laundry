<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'superadmin@nale-laundry.test', 'tenant_id' => null],
            [
                'name' => 'Super Admin',
                'phone' => null,
                'role' => User::ROLE_SUPERADMIN,
                'is_active' => true,
                'password' => Hash::make('superadmin'),
            ]
        );
    }
}
