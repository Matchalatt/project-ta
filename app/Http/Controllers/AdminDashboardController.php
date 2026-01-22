<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User; // <-- 1. Impor model User
use App\Models\Order; // <-- 2. Impor model Order

class AdminDashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard utama untuk admin dengan data dinamis.
     */
    public function index(): View
    {
        // Menghitung total pengguna terdaftar
        $totalUsers = User::count();

        // Menjumlahkan total harga dari semua pesanan yang statusnya sudah lunas ('paid')
        $totalSales = Order::where('status', 'paid')->sum('total_price');

        // Menghitung jumlah pesanan yang statusnya masih 'unpaid'
        $newOrders = Order::where('status', 'unpaid')->count();

        // Mengambil 5 pesanan terbaru untuk ditampilkan sebagai aktivitas
        // Kita juga memuat relasi 'user' dan 'items' untuk ditampilkan di tabel
        $recentActivities = Order::with(['user', 'items'])->latest()->take(5)->get();
        
        // Kirim semua data ke view
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalSales',
            'newOrders',
            'recentActivities'
        ));
    }
}