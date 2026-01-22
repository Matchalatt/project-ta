<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controller Imports
|--------------------------------------------------------------------------
|
| Mengimpor semua controller yang dibutuhkan untuk routing agar kode lebih
| bersih dan terorganisir.
|
*/

// == Controller untuk Pengguna (User) ==
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController; // <-- Diperlukan untuk detail produk
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TroliController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\MidtransController;

// == Controller untuk Admin ==
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminDashboardController;
// Catatan: ProductController digunakan oleh User dan Admin
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\AdminOrderController;

/*
|--------------------------------------------------------------------------
| Rute Web (User & Public)
|--------------------------------------------------------------------------
|
| Rute ini menangani semua interaksi dari sisi pengguna, mulai dari halaman
| selamat datang, otentikasi, hingga proses belanja.
|
*/

// --- Rute untuk Tamu (Guest) ---
// Rute ini hanya bisa diakses oleh pengguna yang belum login.
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showWelcomeForm'])->name('welcome');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// --- Rute untuk Pengguna Terotentikasi ---
// Rute ini memerlukan pengguna untuk login terlebih dahulu.
Route::middleware(['auth'])->group(function () {
    // Halaman utama dan Logout
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // [PEMBARUAN] Rute untuk menampilkan halaman detail produk
    Route::get('/produk/{product}', [ProductController::class, 'show'])->name('produk.show');

    // Halaman statis dan kontak
    Route::get('/about-us', function () { return view('aboutus'); })->name('about-us');
    Route::get('/contact', function () { return view('contact'); })->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

    // Fitur Keranjang Belanja (Troli)
    Route::get('/troli', [TroliController::class, 'index'])->name('troli.index');
    Route::post('/troli/add', [TroliController::class, 'add'])->name('troli.add');
    Route::delete('/troli/remove/{id}', [TroliController::class, 'remove'])->name('troli.remove');

    // Proses Checkout
    Route::post('/checkout', [CheckoutController::class, 'index'])->name('checkout.show');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/repay/{order_code}', [CheckoutController::class, 'repay'])->name('checkout.repay');

    // Halaman Histori Pemesanan
    Route::get('/histori-pesanan', [OrderHistoryController::class, 'index'])->name('order.history');
    Route::delete('/histori-pesanan/{order}', [OrderHistoryController::class, 'destroy'])->name('order.destroy');
});

/*
|--------------------------------------------------------------------------
| Rute Web (Admin)
|--------------------------------------------------------------------------
|
| Semua rute yang berhubungan dengan panel admin dikelompokkan di sini
| dengan prefix '/admin' dan nama 'admin.'.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {
    
    // --- Rute Login Admin (Hanya untuk tamu admin) ---
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    });

    // --- Rute Panel Admin Terproteksi ---
    // Memerlukan login sebagai admin.
    Route::middleware('auth:admin')->group(function () {
        // Dashboard dan Logout Admin
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

        // Manajemen Produk
        // Menggunakan resource controller untuk CRUD dasar
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        // Rute tambahan untuk fungsionalitas khusus
        Route::post('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggleStatus');
        
        // Manajemen Keluhan
        Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
        Route::delete('/complaints/{complaint}', [ComplaintController::class, 'destroy'])->name('complaints.destroy');

        // Manajemen Pesanan
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    });
});

/*
|--------------------------------------------------------------------------
| Rute Callback Pihak Ketiga (Midtrans)
|--------------------------------------------------------------------------
|
| Rute ini tidak memiliki middleware 'web' karena notifikasi dari Midtrans
| tidak menyertakan CSRF token.
|
*/
Route::post('/midtrans/notification', [MidtransController::class, 'notificationHandler'])->name('midtrans.notification');