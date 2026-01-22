<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order; // Diperlukan untuk cek riwayat
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// [PEMBARUAN] Impor KEDUA controller rekomendasi
use App\Http\Controllers\CollaborativeRecommendationController;
use App\Http\Controllers\RecommendationController; // <-- DITAMBAHKAN untuk TF-IDF

class HomeController extends Controller
{
    /**
     * Menampilkan halaman dashboard.
     * [PEMBARUAN] Menerapkan logika hybrid fallback (k-NN -> TF-IDF -> Populer).
     */
    public function index()
    {
        // 1. Ambil semua produk untuk grid utama ("Semua Menu Kami")
        $products = Product::with('options')->latest()->get();

        // 2. Ambil produk populer (untuk tamu & fallback pengguna login)
        $popularProducts = $this->getPopularProducts();

        // 3. Siapkan variabel rekomendasi
        $personalizedRecommendations = collect(); // Default koleksi kosong
        
        // [PEMBARUAN] Judul dinamis untuk ditampilkan di view
        $recommendationTitle = "Snack Terfavorit Pilihan Pelanggan"; 

        $user = Auth::user();

        // 4. Logika HANYA jika user login (sesuai @auth di blade)
        if ($user) {
            
            // ==========================================================
            // [PEMBARUAN UTAMA] Logika Fallback Hybrid
            // ==========================================================

            // Tentukan batas "stabil" untuk k-NN. 
            // Ini adalah jumlah total pesanan 'paid' di SELURUH website.
            // Anda bisa sesuaikan angka ini.
            $minGlobalOrdersForKnn = 50; 
            
            $totalPaidOrders = Order::where('status', 'paid')
                                    ->whereNull('deleted_at') // Pastikan order valid
                                    ->count();

            if ($totalPaidOrders >= $minGlobalOrdersForKnn) {
                
                // --- STRATEGI 1: k-NN (User-Based) ---
                // Data global sudah stabil. Cek data individual user.
                
                // Tentukan batas minimal order per user untuk k-NN
                $minUserOrdersForKnn = 5; 
                
                $paidOrderCount = Order::where('user_id', $user->id)
                                       ->where('status', 'paid')
                                       ->whereNull('deleted_at')
                                       ->count();
                                       
                if ($paidOrderCount >= $minUserOrdersForKnn) {
                    // Data user cukup, jalankan k-NN
                    $recs = CollaborativeRecommendationController::getRecommendationsForUser($user->id);
                    if ($recs->isNotEmpty()) {
                        $personalizedRecommendations = $recs;
                        $recommendationTitle = "Rekomendasi Untuk Anda";
                    }
                }
                // Jika user ini belum punya $minUserOrdersForKnn, 
                // $personalizedRecommendations akan tetap kosong, dan view akan 
                // otomatis menampilkan $popularProducts (Prioritas 3)

            } else {
                
                // --- STRATEGI 2: Content-Based (TF-IDF) Fallback ---
                // Data global BELUM stabil. Coba cari pembelian terakhir user ini.
                
                $lastPurchasedProduct = Product::join('order_items', 'products.id', '=', 'order_items.product_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.user_id', $user->id)
                    ->where('orders.status', 'paid')
                    ->whereNull('orders.deleted_at') // Pastikan pesanan valid
                    ->select('products.*')
                    ->latest('orders.created_at') // Ambil yg paling baru
                    ->first();

                if ($lastPurchasedProduct) {
                    // User punya riwayat, panggil TF-IDF (Content-Based)
                    $recs = RecommendationController::getRecommendationsFor($lastPurchasedProduct);
                    if ($recs->isNotEmpty()) {
                        $personalizedRecommendations = $recs;
                        $recommendationTitle = "Mungkin Anda Suka"; // Judul beda
                    }
                }
                // Jika $lastPurchasedProduct = null (user baru, belum pernah beli),
                // $personalizedRecommendations akan tetap kosong, 
                // dan view akan otomatis menampilkan $popularProducts (Prioritas 3)
            }
        }
        
        // 6. Kirim data ke view
        return view('dashboard', [
            'products' => $products,
            'popularProducts' => $popularProducts,
            'personalizedRecommendations' => $personalizedRecommendations,
            'recommendationTitle' => $recommendationTitle // [PEMBARUAN] Kirim judulnya
        ]);
    }

    /**
     * [HELPER] Mengambil produk terpopuler.
     * (Logika ini tidak berubah dari file Anda sebelumnya)
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