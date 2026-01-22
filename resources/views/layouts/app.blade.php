<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Judul halaman akan dinamis, dengan judul default 'Snack Juara' --}}
    <title>@yield('title', 'Snack Juara')</title>
    
    {{-- Google Fonts & Tailwind CSS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- CSS Styles Global --}}
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
        #cart-count-badge { position: absolute; top: 2px; right: 2px; background-color: #EF4444; color: white; font-size: 11px; font-weight: 600; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transform: scale(0); transition: transform 0.2s ease-out; }
        #cart-count-badge.visible { transform: scale(1); }
        #toast-notification { position: fixed; bottom: 20px; left: 50%; transform: translate(-50%, 150%); transition: transform 0.5s ease-in-out; z-index: 100; }
        #toast-notification.show { transform: translate(-50%, 0); }
    </style>
    {{-- Slot untuk CSS tambahan per halaman --}}
    @stack('styles')
</head>
<body class="antialiased bg-orange-50">

    {{-- =================================== --}}
    {{--         HEADER / NAVBAR             --}}
    {{-- =================================== --}}
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
                {{-- SESUDAH --}}
                <a href="{{ route('troli.index') }}" class="relative inline-block">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{-- INI BAGIAN YANG DITAMBAHKAN --}}
                    <span id="cart-count" 
                        class="absolute -top-2 -right-3 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                        
                        {{-- Menampilkan jumlah awal saat halaman dimuat --}}
                        @php
                            $initialCartCount = 0;
                            if (auth()->check()) {
                                // Jika pengguna login, ambil dari database
                                $cart = \App\Models\Cart::where('user_id', auth()->id())->withCount('items')->first();
                                if ($cart) {
                                    $initialCartCount = $cart->items_count;
                                }
                            } else {
                                // Jika pengguna tamu, ambil dari session
                                $initialCartCount = count(session('cart', []));
                            }
                        @endphp
                        {{ $initialCartCount }}
                    </span>
                    {{-- AKHIR BAGIAN YANG DITAMBAHKAN --}}
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

    {{-- =================================== --}}
    {{--      KONTEN UTAMA SETIAP HALAMAN    --}}
    {{-- =================================== --}}
    <main>
        @yield('content')
    </main>

    {{-- =================================== --}}
    {{--              FOOTER                 --}}
    {{-- =================================== --}}
    <footer class="bg-white border-t border-gray-200">
        <div class="container mx-auto px-6 py-8 text-center text-gray-500">
            <p>&copy; {{ date('Y') }} Snack Juara. All Rights Reserved.</p>
        </div>
    </footer>

    {{-- Elemen Notifikasi "Toast" Global --}}
    <div id="toast-notification" class="bg-gray-800 text-white font-semibold py-2 px-5 rounded-full shadow-lg">
        <p id="toast-message"></p>
    </div>

    {{-- Slot untuk JavaScript tambahan per halaman --}}
    @stack('scripts')
</body>
</html>
