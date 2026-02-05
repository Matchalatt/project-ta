<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class OrderHistoryController extends Controller
{
    /**
     * Menampilkan halaman histori pemesanan pengguna.
     */
    public function index()
    {
        // Secara otomatis menyembunyikan pesanan yang sudah di-"soft delete"
        $orders = Order::with('items.product')
                       ->where('user_id', Auth::id())
                       ->latest()
                       ->get();

        return view('history', compact('orders'));
    }

    /**
     * PEMBARUAN: Method baru untuk "menghapus" (soft delete) pesanan.
     */
    public function destroy(Order $order)
    {
        // Keamanan: Pastikan pengguna hanya bisa menghapus pesanannya sendiri.
        if (Auth::id() !== $order->user_id) {
            abort(403, 'AKSI TIDAK DIIZINKAN.');
        }

        // Lakukan soft delete
        $order->delete();

        // Redirect kembali ke halaman histori dengan pesan sukses
        return redirect()->route('order.history')->with('success', 'Pesanan berhasil dihapus.');
    }
}