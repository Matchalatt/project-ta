<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Impor KEDUA controller rekomendasi
use App\Http\Controllers\CollaborativeRecommendationController;
use App\Http\Controllers\RecommendationController;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman dashboard.
     * [PEMBARUAN] Menerapkan logika Adaptif Bertingkat (Waterfall).
     * Urutan: User-Based -> Content-Based -> Popular Products.
     */
    public function index()
    {
        // 1. Ambil semua produk untuk grid utama ("Semua Menu Kami")
        $products = Product::with('options')->latest()->get();

        // 2. Ambil produk populer (untuk fallback paling akhir & tamu)
        $popularProducts = $this->getPopularProducts();

        // 3. Siapkan variabel rekomendasi default
        $personalizedRecommendations = collect(); 
        $recommendationTitle = "Snack Terfavorit Pilihan Pelanggan"; // Judul Default (Populer)

        $user = Auth::user();

        // 4. Logika HANYA jika user login
        if ($user) {
            
            // ==========================================================
            // [LOGIKA BARU] ADAPTIF BERTINGKAT (WATERFALL)
            // ==========================================================

            // Tentukan Batas Ambang (Threshold)
            $minGlobalOrders = 50; // Syarat Toko Matang
            $minUserOrders = 5;    // Syarat User Matang (Profil terbentuk)
            
            // Hitung Data Real-time
            // Total order 'paid' se-Indonesia (Global)
            $totalPaidOrders = Order::where('status', 'paid')
                                    ->whereNull('deleted_at')
                                    ->count();
            
            // Total order 'paid' milik User ini
            $userOrderCount = Order::where('user_id', $user->id)
                                   ->where('status', 'paid')
                                   ->whereNull('deleted_at')
                                   ->count();

            // ----------------------------------------------------------
            // TAHAP 1: Coba User-Based Collaborative (Prioritas Tertinggi)
            // ----------------------------------------------------------
            // Syarat: Toko sudah punya banyak data (>=50) DAN User sudah aktif (>=5)
            // Alasannya: Kita butuh profil user yang kuat untuk dicocokkan dengan orang lain.
            if ($totalPaidOrders >= $minGlobalOrders && $userOrderCount >= $minUserOrders) {
                
                $recs = CollaborativeRecommendationController::getRecommendationsForUser($user->id);
                
                if ($recs->isNotEmpty()) {
                    $personalizedRecommendations = $recs;
                    $recommendationTitle = "Rekomendasi Dari Komunitas";
                }
            }

            // ----------------------------------------------------------
            // TAHAP 2: Coba Content-Based (Prioritas Kedua)
            // ----------------------------------------------------------
            // Syarat:
            // 1. Variabel rekomendasi MASIH KOSONG (artinya Tahap 1 gagal/tidak memenuhi syarat).
            // 2. TAPI User minimal pernah belanja 1 kali ($userOrderCount > 0).
            // Alasannya: User belum cukup data untuk dicocokkan dengan orang lain, 
            // tapi kita bisa kasih rekomendasi "mirip barang terakhir".
            if ($personalizedRecommendations->isEmpty() && $userOrderCount > 0) {
                
                // Ambil 1 produk terakhir yang dibeli user sebagai acuan
                $lastPurchasedProduct = Product::join('order_items', 'products.id', '=', 'order_items.product_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.user_id', $user->id)
                    ->where('orders.status', 'paid')
                    ->whereNull('orders.deleted_at')
                    ->select('products.*')
                    ->latest('orders.created_at')
                    ->first();

                if ($lastPurchasedProduct) {
                    // Panggil TF-IDF Controller
                    $recs = RecommendationController::getRecommendationsFor($lastPurchasedProduct);
                    
                    if ($recs->isNotEmpty()) {
                        $personalizedRecommendations = $recs;
                        // Judul dinamis agar lebih personal
                        $recommendationTitle = "Karena Anda membeli " . $lastPurchasedProduct->name;
                    }
                }
            }

            // ----------------------------------------------------------
            // TAHAP 3: Popular Products (Default / Fallback Terakhir)
            // ----------------------------------------------------------
            // Jika Tahap 1 & 2 gagal (misal: User baru daftar, 0 transaksi),
            // maka $personalizedRecommendations tetap kosong.
            // Di View (Blade), pastikan logika menampilkan $popularProducts jika koleksi ini kosong.
        }
        
        // 5. Kirim data ke view
        return view('dashboard', [
            'products' => $products,
            'popularProducts' => $popularProducts,
            'personalizedRecommendations' => $personalizedRecommendations,
            'recommendationTitle' => $recommendationTitle
        ]);
    }

    /**
     * [HELPER] Mengambil produk terpopuler.
     * Digunakan untuk tamu atau jika algoritma cerdas gagal.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function getPopularProducts($limit = 5)
    {
        $popularProductIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNull('orders.deleted_at') // Pastikan pesanan tidak dihapus
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->pluck('product_id');

        if ($popularProductIds->isEmpty()) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, count($popularProductIds), '?'));
        return Product::whereIn('id', $popularProductIds)
            ->orderByRaw("FIELD(id, {$placeholders})", $popularProductIds->toArray())
            ->get();
    }
}