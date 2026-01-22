<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin; // Jangan lupa import model Admin
use Illuminate\Support\Facades\Hash; // Import Hash facade

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Hapus data admin yang mungkin sudah ada untuk menghindari duplikat
        Admin::truncate();

        // Buat satu akun admin
        Admin::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // Ganti 'password' dengan password yang aman
        ]);
    }
}