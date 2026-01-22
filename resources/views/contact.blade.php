<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Snack Juara</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Variabel Warna Sesuai Dashboard */
        :root {
            --brand-orange: #F97316; /* Oranye utama (Tailwind Orange 500) */
            --brand-orange-light: #FFF7ED; /* Oranye sangat muda (Tailwind Orange 50) */
            --brand-orange-hover: #FFEDD5; /* Oranye muda untuk hover (Tailwind Orange 100) */
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #374151;
        }

        /* === HEADER & NAVBAR (SAMA PERSIS DENGAN DASHBOARD) === */
        .header-custom {
            background-color: #ffffff;
            position: relative;
            z-index: 10;
        }
        .header-wave {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            transform: rotate(180deg);
            z-index: 5;
        }
        .header-wave svg {
            position: relative;
            display: block;
            width: calc(100% + 1.3px);
            height: 60px;
        }
        .header-wave .shape-fill {
            fill: #FFFFFF;
        }
        .logo-center {
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translate(-50%, 50%);
            z-index: 30;
        }
        .logo-center img {
            border-radius: 50%;
            width: 96px;
            height: 96px;
            object-fit: cover;
            border: 3px solid var(--brand-orange);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .nav-item {
            color: #374151 !important;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: color 0.3s ease;
            position: relative;
            z-index: 15;
        }
        .nav-item:hover {
            color: var(--brand-orange) !important;
        }
        .user-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 20;
            position: relative;
        }
        .cart-button {
            color: #6B7280;
            transition: color 0.3s ease, background-color 0.3s ease;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-button:hover {
            color: var(--brand-orange);
            background-color: var(--brand-orange-hover);
        }
        .profile-dropdown {
            position: relative;
        }
        .profile-button {
            width: 40px;
            height: 40px;
            background-color: #E5E7EB;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .profile-button:hover {
            background-color: var(--brand-orange);
            color: white;
            border-color: #FB923C;
        }
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            width: 200px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #E5E7EB;
            padding: 4px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 50;
        }
        .profile-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .dropdown-header {
            padding: 12px 16px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 14px;
            color: #6B7280;
        }
        .dropdown-header .username {
            font-weight: 600;
            color: #374151;
        }
        .logout-button {
            width: 100%;
            text-align: left;
            padding: 12px 16px;
            font-size: 14px;
            color: #6B7280;
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .logout-button:hover {
            background-color: var(--brand-orange);
            color: white;
        }
    </style>
</head>
<body class="antialiased bg-orange-50">

    <header class="header-custom">
        {{-- ... Kode header Anda tetap sama ... --}}
        <div class="container mx-auto px-6 py-4 relative">
            <nav class="w-full flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="nav-item">Beranda</a>
                    <a href="{{ route('order.history') }}" class="nav-item">Histori Pemesanan</a>
                </div>

                <div class="flex items-center space-x-8">
                    <a href="{{ route('about-us') }}" class="nav-item">About Us</a>
                    <a href="{{ route('contact') }}" class="nav-item font-bold text-orange-500">Contact</a>
                    
                    <div class="user-actions">
                        <a href="{{ route('troli.index') }}" class="cart-button">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </a>
                        
                        <div class="profile-dropdown">
                            <button class="profile-button">
                                {{ substr(auth()->user()->username, 0, 1) }}
                            </button>
                            <div class="dropdown-menu">
                                <div class="dropdown-header">
                                    Halo, <span class="username">{{ auth()->user()->username }}</span>
                                </div>
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="logout-button">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>

        <div class="logo-center">
            <a href="{{ route('dashboard') }}" class="block">
                <img src="{{ asset('images/logo.jpg') }}" alt="Snack Juara Logo">
            </a>
        </div>
        
        <div class="header-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" class="shape-fill"></path>
            </svg>
        </div>
    </header>

    <main>
        {{-- ... Bagian section hero dan info kontak Anda tetap sama ... --}}
        <section class="relative bg-cover bg-center pt-32 pb-20" style="background-image: url('{{ asset('images/contact-background.jpg') }}');">
            <div class="absolute inset-0 bg-orange-500 opacity-80"></div>
            <div class="container mx-auto px-6 text-white text-center relative z-10">
                <h1 class="text-4xl md:text-5xl font-bold">Contact Us</h1>
                <p class="mt-4 text-lg max-w-2xl mx-auto">Punya pertanyaan, kritik, atau saran? Jangan ragu untuk menghubungi kami melalui form di bawah ini!</p>
            </div>
        </section>

        <section class="py-16">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    
                    <a href="c" target="_blank" class="block bg-white p-8 rounded-2xl shadow-lg text-center transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl">
                        <div class="text-orange-500 w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-orange-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Our Location</h3>
                        <p class="text-gray-500">Jl. Plamongansari Kp. Kedung RT 03/RW 12, Semarang, Jawa Tengah</p>
                    </a>

                    <a href="https://wa.me/628983553660" target="_blank" class="block bg-white p-8 rounded-2xl shadow-lg text-center transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl">
                        <div class="text-orange-500 w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-orange-100 rounded-full">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Phone Number</h3>
                        <p class="text-gray-500">(+62) 898-3553-660</p>
                    </a>

                    <a href="mailto:support@snackjuara.com" class="block bg-white p-8 rounded-2xl shadow-lg text-center transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl">
                        <div class="text-orange-500 w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-orange-100 rounded-full">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Email Us</h3>
                        <p class="text-gray-500">support@snackjuara.com</p>
                    </a>

                    <div class="bg-white p-8 rounded-2xl shadow-lg text-center transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl">
                        <div class="text-orange-500 w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-orange-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Working Hours</h3>
                        <p class="text-gray-500">Senin - Sabtu: 09.00 - 17.00</p>
                    </div>

                </div>
            </div>
        </section>

        <section class="pt-0 pb-20">
            <div class="container mx-auto px-6">

                {{-- PEMBARUAN: Menampilkan pesan sukses setelah submit form --}}
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative max-w-4xl mx-auto mb-6" role="alert">
                        <strong class="font-bold">Sukses!</strong>
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                
                <div class="bg-white p-8 md:p-12 rounded-2xl shadow-xl max-w-4xl mx-auto">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2 text-center">Get In Touch With Snack Juara</h2>
                    <p class="text-gray-500 mb-8 text-center">Kami akan merespon pesan Anda sesegera mungkin.</p>
                    
                    {{-- PEMBARUAN: Form action diubah ke route('contact.store') --}}
                    <form action="{{ route('contact.store') }}" method="POST">
                        @csrf
                        <div class="flex flex-col lg:flex-row gap-8 mb-6">
                            
                            <div class="lg:w-1/2 flex flex-col gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Your Name</label>
                                    {{-- PEMBARUAN: Input name sekarang memiliki validasi, old value, dan auto-fill nama user --}}
                                    <input type="text" id="name" name="name" placeholder="Masukkan nama lengkap Anda" value="{{ old('name', auth()->user()->name) }}" required
                                           class="w-full px-4 py-3 bg-gray-50 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition">
                                    {{-- Menampilkan pesan error jika validasi 'name' gagal --}}
                                    @error('name')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Your Email</label>
                                    {{-- PEMBARUAN: Input email sekarang memiliki validasi, old value, dan auto-fill email user --}}
                                    <input type="email" id="email" name="email" placeholder="contoh@email.com" value="{{ old('email', auth()->user()->email) }}" required
                                           class="w-full px-4 py-3 bg-gray-50 border @error('email') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition">
                                    {{-- Menampilkan pesan error jika validasi 'email' gagal --}}
                                    @error('email')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="lg:w-1/2 flex flex-col">
                                <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Your Message</label>
                                {{-- PEMBARUAN: Textarea sekarang memiliki validasi dan old value --}}
                                <textarea id="message" name="message" rows="7" placeholder="Tuliskan pesan Anda di sini..." required
                                          class="w-full h-full px-4 py-3 bg-gray-50 border @error('message') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition resize-none">{{ old('message') }}</textarea>
                                {{-- Menampilkan pesan error jika validasi 'message' gagal --}}
                                @error('message')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="text-center mt-8">
                            <button type="submit" class="bg-orange-500 text-white font-bold py-3 px-10 rounded-full hover:bg-orange-600 transition-all duration-300 transform hover:scale-105">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-white border-t border-gray-200">
        <div class="container mx-auto px-6 py-8 text-center text-gray-500">
            <p>© {{ date('Y') }} Snack Juara. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>