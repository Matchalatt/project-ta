<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class OrderStatusController extends Controller
{
    /**
     * Menampilkan halaman ketika pembayaran berhasil.
     */
    public function success(Request $request)
    {
        // Cari order berdasarkan order_code yang dikirim dari Midtrans/URL
        $order = Order::where('order_code', $request->query('order_id'))->first();

        // Jika order tidak ditemukan, tampilkan halaman error
        if (!$order) {
            abort(404, 'Pesanan tidak ditemukan.');
        }

        // Lupakan session checkout items karena sudah berhasil
        session()->forget('checkout_items');
        session()->forget('cart'); // Anda juga bisa membersihkan keranjang di sini

        return view('order.success', compact('order'));
    }

    /**
     * Menampilkan halaman ketika pembayaran masih tertunda (pending).
     */
    public function pending(Request $request)
    {
        $order = Order::where('order_code', $request->query('order_id'))->first();

        if (!$order) {
            abort(404, 'Pesanan tidak ditemukan.');
        }

        return view('order.pending', compact('order'));
    }

    /**
     * Menampilkan halaman ketika pembayaran gagal.
     */
    public function failed(Request $request)
    {
        $order = Order::where('order_code', $request->query('order_id'))->first();

        if (!$order) {
            abort(404, 'Pesanan tidak ditemukan.');
        }

        return view('order.failed', compact('order'));
    }
}