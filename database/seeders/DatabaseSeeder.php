<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Catatan: tanpa WithoutModelEvents — MenuObserver harus terpicu
     * agar permission {slug}.{aksi} ikut dibuat saat seeding menu.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            MenuSeeder::class,
            UserSeeder::class,
            SurveySeeder::class,
        ]);
    }
}
