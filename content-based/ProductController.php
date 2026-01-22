<?php

// Lokasi file: app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\RecommendationController; // <-- DITAMBAHKAN: Untuk memanggil sistem rekomendasi

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Metode untuk Halaman Publik (User)
    |--------------------------------------------------------------------------
    | Metode ini menangani apa yang dilihat oleh pengguna di sisi depan website.
    */

    /**
     * [PEMBARUAN] Menampilkan halaman detail untuk satu produk.
     * Metode ini mengambil data produk spesifik, memanggil sistem rekomendasi
     * untuk produk serupa, dan mengirimkan semua data ke view.
     *
     * @param  \App\Models\Product  $product (Route Model Binding)
     * @return \Illuminate\View\View
     */
    public function show(Product $product)
    {
        // Memuat relasi 'options' untuk memastikan data varian produk tersedia
        $product->load('options');

        // Memanggil metode statis dari RecommendationController untuk mendapatkan produk serupa
        $recommendations = RecommendationController::getRecommendationsFor($product);

        // Mengirim data produk utama dan data rekomendasinya ke view 'produk.show'
        return view('produk.show', [
            'product' => $product,
            'recommendations' => $recommendations
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Metode untuk Panel Admin
    |--------------------------------------------------------------------------
    | Metode-metode di bawah ini dikhususkan untuk manajemen produk oleh admin.
    */

    /**
     * Menampilkan daftar semua produk di halaman admin.
     */
    public function index()
    {
        $products = Product::latest()->get();
        return view('admin.products.index', compact('products'));
    }

    /**
     * Menampilkan form untuk membuat produk baru.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Menyimpan produk baru beserta variannya ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'category' => 'required|in:jajanan,paket',
            'tags' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.name' => 'required_with:options|string|max:255',
            'options.*.price' => 'required_with:options|numeric',
        ]);

        // 2. Proses upload gambar
        $imageName = time().'.'.$request->image->extension();
        $request->image->move(public_path('products'), $imageName);

        // 3. Buat produk utama
        $product = Product::create([
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'category' => $request->category,
            'tags' => $request->tags,
            'image' => $imageName,
        ]);

        // 4. Jika ada varian, simpan ke database
        if ($request->has('options') && is_array($request->options)) {
            foreach ($request->options as $optionData) {
                if (!empty($optionData['name']) && !empty($optionData['price'])) {
                    // Membuat varian yang berelasi dengan produk yang baru saja dibuat
                    $product->options()->create([
                        'name' => $optionData['name'],
                        'price' => $optionData['price'],
                    ]);
                }
            }
        }

        // 5. Redirect kembali ke halaman daftar produk dengan pesan sukses
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Mengubah status ketersediaan produk (tersedia/habis).
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(Product $product)
    {
        // Menggunakan operator NOT (!) untuk membalik nilai boolean
        $product->is_available = !$product->is_available;
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Status produk berhasil diperbarui!');
    }

    /**
     * Menghapus produk dari database beserta file gambarnya.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        // 1. Hapus file gambar dari folder public/products
        $imagePath = public_path('products/' . $product->image);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        // 2. Hapus data produk dari database (ini akan otomatis menghapus varian terkait jika relasi di-setting dengan onDelete('cascade'))
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus!');
    }
}