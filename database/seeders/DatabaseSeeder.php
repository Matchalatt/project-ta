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
        // Panggil AdminSeeder yang baru Anda buat
        $this->call([
            AdminSeeder::class,
             UserSeeder::class,
            // Anda bisa menambahkan Seeder lain di sini jika ada
        ]);
    }
}