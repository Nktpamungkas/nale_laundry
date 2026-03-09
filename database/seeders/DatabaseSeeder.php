<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeder minimal: hanya buat akun superadmin global.
        $this->call([
            MasterAdminSeeder::class,
        ]);
    }
}
