<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class AdminOrderController extends Controller
{
    /**
     * Menampilkan halaman daftar semua pesanan.
     */
    public function index()
    {
        // Ambil semua pesanan, urutkan dari yang paling baru.
        // Gunakan with() untuk memuat relasi items dan user agar efisien (menghindari N+1 problem).
        $orders = Order::with(['items', 'user'])->latest()->get();

        // Kirim data pesanan ke view
        return view('admin.orders.index', compact('orders'));
    }
}