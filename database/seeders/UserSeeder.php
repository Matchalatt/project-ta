<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar 50 user yang Anda tentukan
        $users = [
            ['username' => 'adisaputra', 'no_tlpn' => '081234567890'],
            ['username' => 'budisantoso', 'no_tlpn' => '081345678901'],
            ['username' => 'citralestari', 'no_tlpn' => '081456789012'],
            ['username' => 'dewianggraini', 'no_tlpn' => '081567890123'],
            ['username' => 'ekawijaya', 'no_tlpn' => '081678901234'],
            ['username' => 'fajarnugroho', 'no_tlpn' => '081789012345'],
            ['username' => 'gitapermata', 'no_tlpn' => '081890123456'],
            ['username' => 'hendragunawan', 'no_tlpn' => '081901234567'],
            ['username' => 'indahsari', 'no_tlpn' => '082123456789'],
            ['username' => 'jokosusilo', 'no_tlpn' => '082234567890'],
            ['username' => 'kartikachandra', 'no_tlpn' => '082345678901'],
            ['username' => 'lestariindah', 'no_tlpn' => '085212345678'],
            ['username' => 'muhammadrizky', 'no_tlpn' => '085323456789'],
            ['username' => 'novitasari', 'no_tlpn' => '085612345678'],
            ['username' => 'oscarmahendra', 'no_tlpn' => '085723456789'],
            ['username' => 'putriamelia', 'no_tlpn' => '085812345678'],
            ['username' => 'qoriahhalim', 'no_tlpn' => '087712345678'],
            ['username' => 'rahmathidayat', 'no_tlpn' => '087823456789'],
            ['username' => 'sitinurhaliza', 'no_tlpn' => '088123456789'],
            ['username' => 'suryaputra', 'no_tlpn' => '088234567890'],
            ['username' => 'triutami', 'no_tlpn' => '088812345678'],
            ['username' => 'ujangmulyana', 'no_tlpn' => '089512345678'],
            ['username' => 'vinaandriani', 'no_tlpn' => '089623456789'],
            ['username' => 'wahyuhidayat', 'no_tlpn' => '089712345678'],
            ['username' => 'yuliapuspita', 'no_tlpn' => '089823456789'],
            ['username' => 'zainalabidin', 'no_tlpn' => '089912345678'],
            ['username' => 'amirmahmud', 'no_tlpn' => '081298765432'],
            ['username' => 'bellaoctavia', 'no_tlpn' => '081387654321'],
            ['username' => 'chandrawijaya', 'no_tlpn' => '081476543210'],
            ['username' => 'dinilestari', 'no_tlpn' => '081565432109'],
            ['username' => 'erwinprasetyo', 'no_tlpn' => '081654321098'],
            ['username' => 'fitriani', 'no_tlpn' => '081743210987'],
            ['username' => 'galihsantoso', 'no_tlpn' => '081832109876'],
            ['username' => 'hanayuliana', 'no_tlpn' => '081921098765'],
            ['username' => 'iqbalramadhan', 'no_tlpn' => '082187654321'],
            ['username' => 'jihannabila', 'no_tlpn' => '082276543210'],
            ['username' => 'krisnamurti', 'no_tlpn' => '082365432109'],
            ['username' => 'lindawati', 'no_tlpn' => '085276543210'],
            ['username' => 'maulanayusuf', 'no_tlpn' => '085365432109'],
            ['username' => 'ninaagustina', 'no_tlpn' => '085676543210'],
            ['username' => 'okisetiawan', 'no_tlpn' => '085765432109'],
            ['username' => 'pratiwi', 'no_tlpn' => '085876543210'],
            ['username' => 'qonitaghaida', 'no_tlpn' => '087776543210'],
            ['username' => 'ramawijaya', 'no_tlpn' => '087865432109'],
            ['username' => 'siskaapriani', 'no_tlpn' => '088176543210'],
            ['username' => 'tonifirmansyah', 'no_tlpn' => '088265432109'],
            ['username' => 'umikulsum', 'no_tlpn' => '088876543210'],
            ['username' => 'vickyprasetyo', 'no_tlpn' => '089576543210'],
            ['username' => 'windaastuti', 'no_tlpn' => '089665432109'],
            ['username' => 'yogapratama', 'no_tlpn' => '089776543210'],
        ];

        // Looping untuk membuat setiap user dari daftar
        foreach ($users as $userData) {
            // Menggunakan firstOrCreate untuk menghindari duplikasi jika seeder dijalankan lagi
            User::firstOrCreate(
                ['username' => $userData['username']], // Cek berdasarkan username yang unik
                [
                    'no_tlpn' => $userData['no_tlpn'],
                    'password' => Hash::make('12345678'), // Password tetap sama untuk semua
                ]
            );
        }
    }
}