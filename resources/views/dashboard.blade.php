<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Snack Juara</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    {{-- CSS Styles --}}
    <style>
        :root {
            --brand-orange: #F97316;
            --brand-orange-light: #FFF7ED;
            --brand-orange-hover: #FFEDD5;
        }
        body { font-family: 'Poppins', sans-serif; color: #374151; }
        .header-custom { background-color: #ffffff; position: relative; z-index: 10; }
        .header-wave { position: absolute; bottom: -1px; left: 0; width: 100%; overflow: hidden; line-height: 0; transform: rotate(180deg); z-index: 5; }
        .header-wave svg { position: relative; display: block; width: calc(100% + 1.3px); height: 60px; }
        .header-wave .shape-fill { fill: #FFFFFF; }
        .logo-center { position: absolute; left: 50%; bottom: 0; transform: translate(-50%, 50%); z-index: 30; }
        .logo-center img { border-radius: 50%; width: 96px; height: 96px; object-fit: cover; border: 3px solid var(--brand-orange); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .nav-item { color: #374151 !important; font-weight: 600; font-size: 16px; text-decoration: none; transition: color 0.3s ease; position: relative; z-index: 15; }
        .nav-item:hover { color: var(--brand-orange) !important; }
        .user-actions { display: flex; align-items: center; gap: 16px; z-index: 20; position: relative; }
        .cart-button { color: #6B7280; transition: color 0.3s ease, background-color 0.3s ease; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; position: relative; }
        .cart-button:hover { color: var(--brand-orange); background-color: var(--brand-orange-hover); }
        .profile-dropdown { position: relative; }
        .profile-button { width: 40px; height: 40px; background-color: #E5E7EB; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #374151; font-weight: 700; font-size: 16px; transition: all 0.3s ease; cursor: pointer; border: 2px solid transparent; }
        .profile-button:hover { background-color: var(--brand-orange); color: white; border-color: #FB923C; }
        .dropdown-menu { position: absolute; right: 0; top: 100%; margin-top: 8px; width: 200px; background-color: white; border-radius: 8px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); border: 1px solid #E5E7EB; padding: 4px 0; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 50; }
        .profile-dropdown:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-header { padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 14px; color: #6B7280; }
        .dropdown-header .username { font-weight: 600; color: #374151; }
        .logout-button { width: 100%; text-align: left; padding: 12px 16px; font-size: 14px; color: #6B7280; background: none; border: none; cursor: pointer; transition: all 0.3s ease; }
        .logout-button:hover { background-color: var(--brand-orange); color: white; }
        .hero-section-video { position: relative; height: 60vh; display: flex; align-items: center; justify-content: center; overflow: hidden; text-align: center; }
        .hero-video-bg { position: absolute; top: 50%; left: 50%; width: auto; height: auto; min-width: 100%; min-height: 100%; transform: translate(-50%, -50%); z-index: 1; }
        .hero-overlay-video { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 2; }
        .hero-content-video { position: relative; z-index: 3; }
        .category-btn-custom { color: #6c757d; font-weight: 600; padding-bottom: 8px; border-bottom: 3px solid transparent; transition: color 0.3s ease, border-color 0.3s ease; cursor: pointer; }
        .category-btn-custom:hover { color: var(--brand-orange); }
        .category-btn-custom.active { color: var(--brand-orange); border-bottom-color: var(--brand-orange); }
        .menu-card-custom { background-color: #ffffff; border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .menu-card-custom:hover { transform: translateY(-8px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .menu-card-habis { position: relative; opacity: 0.6; pointer-events: none; }
        .menu-card-habis:hover { transform: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stamp-habis { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg); background-color: rgba(239, 68, 68, 0.85); color: white; font-size: 1.5rem; font-weight: 700; padding: 8px 32px; border: 3px solid white; border-radius: 8px; text-transform: uppercase; z-index: 5; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        #cart-count-badge { position: absolute; top: 2px; right: 2px; background-color: #EF4444; color: white; font-size: 11px; font-weight: 600; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transform: scale(0); transition: transform 0.2s ease-out; }
        #cart-count-badge.visible { transform: scale(1); }
        #toast-notification { position: fixed; bottom: 20px; left: 50%; transform: translate(-50%, 150%); transition: transform 0.5s ease-in-out; z-index: 100; }
        #toast-notification.show { transform: translate(-50%, 0); }
    </style>
</head>
<body class="antialiased bg-orange-50">

    <header class="header-custom">
        <div class="container mx-auto px-6 py-4 relative">
            <nav class="w-full flex justify-between items-center">
                {{-- Navigasi Kiri --}}
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="nav-item">Beranda</a>
                    <a href="{{ route('order.history') }}" class="nav-item">Histori Pemesanan</a>
                </div>
                {{-- Navigasi Kanan --}}
                <div class="flex items-center space-x-8">
                    <a href="{{ route('about-us') }}" class="nav-item">About Us</a>
                    <a href="{{ route('contact') }}" class="nav-item">Contact</a>
                    <div class="user-actions">
                        <a href="{{ route('troli.index') }}" class="cart-button">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span id="cart-count-badge" class="{{ session('troli') && count(session('troli')) > 0 ? 'visible' : '' }}">
                                {{ session('troli') ? count(session('troli')) : 0 }}
                            </span>
                        </a>
                        <div class="profile-dropdown">
                            <button class="profile-button">{{ substr(auth()->user()->username, 0, 1) }}</button>
                            <div class="dropdown-menu">
                                <div class="dropdown-header">Halo, <span class="username">{{ auth()->user()->username }}</span></div>
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="logout-button">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
        <div class="logo-center">
            <a href="{{ route('dashboard') }}" class="block"><img src="{{ asset('images/logo.jpg') }}" alt="Snack Juara Logo"></a>
        </div>
        <div class="header-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" class="shape-fill"></path>
            </svg>
        </div>
    </header>

    <main>
        {{-- Hero Section dengan Video Background --}}
        <section class="hero-section-video">
            <video class="hero-video-bg" autoplay loop muted playsinline>
                <source src="{{ asset('video/Video_Jajanan_Pasar_Detik_tanpa_audio.mp4') }}" type="video/mp4">
                Browser Anda tidak mendukung tag video.
            </video>
            <div class="hero-overlay-video"></div>
            <div class="hero-content-video container mx-auto px-6 text-white text-center pt-28 pb-16">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Mau pesan apa hari ini?</h1>
                <div class="max-w-2xl mx-auto">
                    <form class="relative" id="search-form">
                        <input type="search" id="search-input" name="search" placeholder="Cari jajanan favoritmu..." class="w-full p-4 pr-16 rounded-full bg-white border-2 border-transparent focus:ring-2 focus:ring-orange-400 focus:outline-none text-gray-800 placeholder-gray-500">
                        <button type="submit" class="absolute right-2.5 top-1/2 -translate-y-1/2 bg-orange-500 hover:bg-orange-600 rounded-full p-2.5 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        {{-- Section Daftar Produk & Rekomendasi --}}
        <section class="py-16">
            <div class="container mx-auto px-6">

                {{-- ======================================================= --}}
                {{-- [KODE SUDAH DIPERBAIKI] BLOK LOGIKA REKOMENDASI GABUNGAN --}}
                {{-- ======================================================= --}}

                @if(isset($personalizedRecommendations) && $personalizedRecommendations->isNotEmpty())
                    {{-- 
                      Block ini akan tampil jika $personalizedRecommendations berisi data.
                      Data ini bisa berasal dari k-NN (jika user lama) ATAU TF-IDF (jika k-NN belum stabil).
                      Judulnya akan dinamis ("Rekomendasi Untuk Anda" atau "Mungkin Anda Suka").
                    --}}
                    <div class="mb-20">
                        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">{{ $recommendationTitle }}</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
                            @foreach ($personalizedRecommendations as $product)
                                <a href="{{ route('produk.show', $product) }}" class="block menu-card-custom">
                                    <div class="card-content">
                                        <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                                        <div class="p-4">
                                            <h3 class="font-bold text-lg mb-1">{{ $product->name }}</h3>
                                            <p class="text-orange-500 font-semibold">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif(isset($popularProducts) && $popularProducts->isNotEmpty())
                    {{-- 
                      Block ini adalah FALLBACK TERAKHIR.
                      Ini akan tampil jika:
                      1. Pengguna adalah TAMU (@guest).
                      2. Pengguna adalah USER BARU (@auth) yang belum punya riwayat belanja.
                      Judulnya akan "Snack Terfavorit Pilihan Pelanggan" (diatur oleh HomeController).
                    --}}
                    <div class="mb-20">
                        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">{{ $recommendationTitle }}</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
                            @foreach ($popularProducts as $product)
                               <a href="{{ route('produk.show', $product) }}" class="block menu-card-custom">
                                    <div class="card-content">
                                        <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                                        <div class="p-4">
                                            <h3 class="font-bold text-lg mb-1">{{ $product->name }}</h3>
                                            <p class="text-orange-500 font-semibold">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                {{-- ======================================================= --}}
                {{-- AKHIR BLOK LOGIKA REKOMENDASI --}}
                {{-- ======================================================= --}}
                
                <div class="border-t pt-16">
                    <h2 class="text-3xl font-bold text-gray-800 mb-12 text-center">Semua Menu Kami</h2>
                    <div class="flex justify-center items-center space-x-6 md:space-x-10 mb-12">
                        <button class="category-btn-custom active" data-filter="semua">Semua</button>
                        <button class="category-btn-custom" data-filter="jajanan">Jajanan</button>
                        <button class="category-btn-custom" data-filter="paket">Paket</button>
                    </div>
            
                    <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
                        @forelse ($products as $product)
                            <a href="{{ route('produk.show', $product) }}" 
                               class="block menu-card-custom @if(!$product->is_available) menu-card-habis @endif"
                               data-category="{{ $product->category }}"
                               data-name="{{ $product->name }}">
                                
                                @if(!$product->is_available)
                                    <div class="stamp-habis">Habis</div>
                                @endif

                                <div class="card-content">
                                    <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                                    <div class="p-4">
                                        <h3 class="font-bold text-lg mb-1">{{ $product->name }}</h3>
                                        <p class="text-orange-500 font-semibold">
                                            @if($product->options->isNotEmpty())
                                                Mulai dari Rp {{ number_format($product->options->min('price'), 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full text-center py-10">
                                <p class="text-gray-500 text-lg">Oops! Belum ada produk yang tersedia saat ini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-gray-200">
        <div class="container mx-auto px-6 py-8 text-center text-gray-500">
            <p>&copy; {{ date('Y') }} Snack Juara. All Rights Reserved.</p>
        </div>
    </footer>

    {{-- ELEMEN NOTIFIKASI "TOAST" --}}
    <div id="toast-notification" class="bg-gray-800 text-white font-semibold py-2 px-5 rounded-full shadow-lg">
        <p id="toast-message">Produk ditambahkan!</p>
    </div>

    {{-- SCRIPT --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const categoryButtons = document.querySelectorAll('.category-btn-custom');
        const productGridCards = document.querySelectorAll('#product-grid a.menu-card-custom');

        // --- LOGIKA FILTER KATEGORI ---
        categoryButtons.forEach(button => {
            button.addEventListener('click', function () {
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const filter = this.dataset.filter;
                productGridCards.forEach(card => {
                    if (filter === 'semua' || card.dataset.category === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // --- LOGIKA PENCARIAN CLIENT-SIDE ---
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault(); 
            const searchTerm = searchInput.value.toLowerCase().trim();
            let productsFound = 0;

            productGridCards.forEach(card => {
                const productName = card.dataset.name.toLowerCase();
                if (productName.includes(searchTerm)) {
                    card.style.display = 'block';
                    productsFound++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (productsFound === 0 && searchTerm !== '') {
                showToast('Produk tidak ditemukan.');
            }
        });
        
        searchInput.addEventListener('input', function() {
            searchForm.dispatchEvent(new Event('submit'));
        });
        
        function showToast(message) {
            const toast = document.getElementById('toast-notification');
            const toastMessage = document.getElementById('toast-message');
            toastMessage.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }
    });
    </script>
</body>
</html>