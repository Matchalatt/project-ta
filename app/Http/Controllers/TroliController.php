<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product; // <-- [DIPERBARUI] Diperlukan untuk fallback TF-IDF
use App\Models\ProductOption;
use App\Models\Cart;
use App\Models\CartItem;
// [DIPERBARUI] Impor KEDUA controller
use App\Http\Controllers\CollaborativeRecommendationController;
use App\Http\Controllers\RecommendationController; // <-- DITAMBAHKAN untuk TF-IDF

class TroliController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja.
     * [PEMBARUAN] Menerapkan logika hybrid fallback (k-NN -> TF-IDF).
     */
    public function index()
    {
        $cartItems = [];
        $productIdsInCart = [];
        $lastAddedProductId = null; // <-- [BARU] Untuk menyimpan ID produk referensi

        if (Auth::check()) {
            // ===============================================
            // Kasus 1: Pengguna SUDAH LOGIN
            // ===============================================
            $user = Auth::user();
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            
            // [PEMBARUAN] Urutkan berdasarkan data terbaru (DESC) untuk menemukan item terakhir
            $dbItems = $cart->items()->with(['product', 'option'])
                             ->orderBy('created_at', 'desc') 
                             ->get();

            foreach ($dbItems as $item) {
                $cartItemId = $item->product_id . '-' . ($item->product_option_id ?? '');
                $cartItems[$cartItemId] = [
                    "db_id"       => $item->id,
                    "name"        => $item->product->name,
                    "quantity"    => $item->quantity,
                    "price"       => $item->option ? $item->option->price : $item->product->price,
                    "image"       => $item->product->image,
                    "notes"       => $item->notes,
                    "option_name" => $item->option ? $item->option->name : null,
                ];
                $productIdsInCart[] = $item->product_id;
            }
            
            // [BARU] Ambil ID produk terakhir yang ditambahkan
            if ($dbItems->isNotEmpty()) {
                $lastAddedProductId = $dbItems->first()->product_id;
            }

        } else {
            // ===============================================
            // Kasus 2: Pengguna adalah TAMU (BELUM LOGIN)
            // ===============================================
            $sessionCart = session()->get('cart', []);
            $cartItems = $sessionCart;

            foreach ($sessionCart as $cartItemId => $details) {
                list($productId, ) = explode('-', $cartItemId . '-');
                $productIdsInCart[] = (int)$productId;
            }
            
            // [BARU] Ambil ID produk terakhir yang ditambahkan
            if (!empty($sessionCart)) {
                $lastItemKey = array_key_last($sessionCart); // Ambil key terakhir
                list($productId, ) = explode('-', $lastItemKey . '-');
                $lastAddedProductId = (int)$productId;
            }
        }

        // =========================================================================
        // [PEMBARUAN UTAMA] Logika Hybrid Fallback
        // =========================================================================
        
        // --- STRATEGI 1: Coba k-NN (Collaborative) dulu ---
        $recommendations = CollaborativeRecommendationController::getRecommendationsForCart(array_unique($productIdsInCart));
        $recommendationTitle = "Pelanggan Lain Juga Membeli"; // Judul default

        // --- STRATEGI 2: Fallback ke TF-IDF (Content-Based) ---
        // Jika k-NN gagal (kosong) DAN kita punya referensi produk (dari item terakhir)
        if ($recommendations->isEmpty() && $lastAddedProductId) {
            
            // 1. Ambil model Produk untuk referensi
            $referenceProduct = Product::find($lastAddedProductId);

            if ($referenceProduct) {
                // 2. Panggil controller TF-IDF
                $recs_tfidf = RecommendationController::getRecommendationsFor($referenceProduct);
                
                // 3. [PENTING] Saring rekomendasi agar tidak menampilkan item yg sudah ada di keranjang
                $filtered_recs = $recs_tfidf->reject(function ($product) use ($productIdsInCart) {
                    return in_array($product->id, array_unique($productIdsInCart));
                });

                if ($filtered_recs->isNotEmpty()) {
                    $recommendations = $filtered_recs;
                    $recommendationTitle = "Mungkin Anda Juga Suka"; // Ganti judul
                }
            }
        }

        // Kirim data ke view
        return view('troli', [
            'cart' => $cartItems,
            'recommendations' => $recommendations, // <-- [DIUBAH] Variabel umum
            'recommendationTitle' => $recommendationTitle // <-- [BARU] Judul dinamis
        ]);
    }


    /**
     * Menambahkan item ke keranjang.
     * (Logika ini tidak berubah dari file Anda sebelumnya)
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id'        => 'required|exists:products,id',
            'quantity'          => 'required|integer|min:1',
            'product_option_id' => 'nullable|exists:product_options,id',
            'notes'             => 'nullable|string',
        ]);

        $cartCount = 0;

        if (Auth::check()) {
            // ============ LOGIKA UNTUK PENGGUNA LOGIN (DATABASE) ============
            $user = Auth::user();
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $request->product_id)
                ->where('product_option_id', $request->product_option_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $request->quantity;
                $existingItem->save();
            } else {
                CartItem::create([
                    'cart_id'           => $cart->id,
                    'product_id'        => $request->product_id,
                    'product_option_id' => $request->product_option_id,
                    'quantity'          => $request->quantity,
                    'notes'             => $request->notes,
                ]);
            }
            $cartCount = $cart->items()->count();
        } else {
            // ============ LOGIKA UNTUK PENGGUNA TAMU (SESSION) ============
            $product = Product::findOrFail($request->product_id);
            $option = $request->product_option_id ? ProductOption::findOrFail($request->product_option_id) : null;
            $cart = session()->get('cart', []);
            $cartItemId = $option ? $product->id . '-' . $option->id : $product->id . '-';

            if (isset($cart[$cartItemId])) {
                $cart[$cartItemId]['quantity'] += $request->quantity;
            } else {
                $cart[$cartItemId] = [
                    "name"        => $product->name,
                    "quantity"    => (int)$request->quantity,
                    "price"       => $option ? $option->price : $product->price,
                    "image"       => $product->image,
                    "notes"       => $request->notes,
                    "option_name" => $option ? $option->name : null,
                ];
            }
            session()->put('cart', $cart);
            $cartCount = count($cart);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Produk berhasil ditambahkan!',
            'cartCount' => $cartCount
        ]);
    }

    /**
     * Menghapus item dari keranjang.
     * (Logika ini tidak berubah dari file Anda sebelumnya)
     */
    public function remove($id)
    {
        if (Auth::check()) {
            CartItem::destroy($id);
        } else {
            $cart = session()->get('cart', []);
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put('cart', $cart);
            }
        }

        return redirect()->route('troli.index')->with('success', 'Produk berhasil dihapus dari keranjang.');
    }

    /**
     * Memindahkan item dari session ke database setelah pengguna berhasil login.
     * (Logika ini tidak berubah dari file Anda sebelumnya)
     */
    public function sync()
    {
        $sessionCart = session()->get('cart');
        if (!$sessionCart) {
            return;
        }

        $user = Auth::user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($sessionCart as $cartItemId => $details) {
            list($productId, $optionId) = explode('-', $cartItemId . '-');

            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $productId)
                ->where('product_option_id', $optionId ?: null)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $details['quantity'];
                $existingItem->save();
            } else {
                CartItem::create([
                    'cart_id'           => $cart->id,
                    'product_id'        => $productId,
                    'product_option_id' => $optionId ?: null,
                    'quantity'          => $details['quantity'],
                    'notes'             => $details['notes'],
                ]);
            }
        }

        session()->forget('cart');
    }
}