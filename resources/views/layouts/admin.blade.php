<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Judul halaman akan dinamis sesuai dengan halaman yang dibuka --}}
    <title>@yield('title', 'Admin Panel')</title>
    {{-- Memuat pustaka Tailwind CSS dari CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen bg-gray-200">
        {{-- ============================================= --}}
        {{--                   SIDEBAR                     --}}
        {{-- ============================================= --}}
        <div class="fixed w-64 h-full bg-gray-800 text-white flex flex-col">
            <div class="px-8 py-6">
                <h2 class="text-2xl font-semibold">Admin Panel</h2>
            </div>
            <nav class="flex-1 px-4 py-2 space-y-2">
                {{-- Tautan ke Dashboard Admin --}}
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 rounded-md {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1V10a1 1 0 00-1-1H7a1 1 0 00-1 1v10a1 1 0 001 1h3z" />
                    </svg>
                    Dashboard
                </a>
                {{-- Tautan ke Manajemen Pengguna (belum aktif) --}}
                <a href="{{ route('admin.complaints.index') }}" class="flex items-center px-4 py-2 rounded-md {{ request()->routeIs('admin.complaints.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A6.002 6.002 0 003 15v1h12v-1a6.002 6.002 0 00-3-5.197z" />
                    </svg>
                    Keluhan User
                </a>
                {{-- Tautan ke Manajemen Produk --}}
                <a href="{{ route('admin.products.index') }}" class="flex items-center px-4 py-2 rounded-md {{ request()->routeIs('admin.products.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Products
                </a>
                <a href="{{ route('admin.orders.index') }}" class="flex items-center px-4 py-2 rounded-md {{ request()->routeIs('admin.products.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Orders
                </a>
            </nav>
            <div class="px-4 py-2 border-t border-gray-700">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        {{-- ============================================= --}}
        {{--                KONTEN UTAMA                 --}}
        {{-- ============================================= --}}
        <div class="flex-1 flex flex-col ml-64">
            <header class="bg-white shadow p-6">
                <div class="max-w-7xl mx-auto">
                    {{-- Header halaman akan dinamis --}}
                    <h1 class="text-3xl font-bold text-gray-900">
                       @yield('header')
                    </h1>
                </div>
            </header>
            <main class="flex-1 p-8">
                {{-- Konten spesifik per halaman akan dimuat di sini --}}
                @yield('content')
            </main>
        </div>
    </div>
    
    {{-- Tempat untuk script Javascript tambahan jika diperlukan --}}
    @stack('scripts')
</body>
</html>