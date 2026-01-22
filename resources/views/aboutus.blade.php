<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Snack Juara</title>
    
    {{-- Font Poppins --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Variabel Warna Baru Sesuai Referensi */
        :root {
            --brand-orange: #F97316; /* Oranye utama (Tailwind Orange 500) */
            --brand-orange-light: #FFF7ED; /* Oranye sangat muda (Tailwind Orange 50) */
            --brand-orange-hover: #FFEDD5; /* Oranye muda untuk hover (Tailwind Orange 100) */
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #374151; /* Warna teks default (abu-abu tua) */
        }

        /* === STYLE HEADER & NAVBAR === */
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
            transition: color 0.3s ease;
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

        /* === STYLE GALERI AKORDEON UTAMA === */
        .accordion-container { display: flex; width: 100%; height: 450px; gap: 1rem; }
        .accordion-item { position: relative; flex: 1; border-radius: 1rem; overflow: hidden; transition: flex 0.8s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
        .accordion-item:hover { flex: 7; }
        .accordion-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .accordion-item:hover img { transform: scale(1.05); }
        .accordion-caption { position: absolute; bottom: 1.5rem; left: 1.5rem; color: white; font-weight: 700; text-shadow: 2px 2px 4px rgba(0,0,0,0.6); opacity: 0; transform: translateY(20px); transition: opacity 0.5s ease 0.3s, transform 0.5s ease 0.3s; }
        .accordion-item:hover .accordion-caption { opacity: 1; transform: translateY(0); }
        .accordion-caption.large { font-size: 1.5rem; }
        .accordion-caption.small { font-size: 1.125rem; }
        
        /* === STYLE GALERI AKORDEON KECIL (DIPERKECIL) === */
        .mini-accordion-container { display: flex; width: 100%; height: 300px; gap: 0.5rem; }
        .mini-accordion-item { position: relative; flex: 1; border-radius: 0.75rem; overflow: hidden; transition: flex 0.8s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
        .mini-accordion-item:hover { flex: 5; }
        .mini-accordion-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .mini-accordion-item:hover img { transform: scale(1.05); }
        .mini-accordion-item:hover .accordion-caption { opacity: 1; transform: translateY(0); }

        /* === STYLE UTILITY === */
        .text-brand-orange { color: var(--brand-orange); }
        .icon-box { background-color: var(--brand-orange-hover); padding: 1rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        .icon-box svg { color: var(--brand-orange); }
    </style>
</head>
<body class="antialiased bg-orange-50">

    <header class="header-custom">
        <div class="container mx-auto px-6 py-4 relative">
            <nav class="w-full flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="nav-item">Beranda</a>
                    <a href="{{ route('order.history') }}" class="nav-item">Histori Pemesanan</a>
                </div>

                <div class="flex items-center space-x-8">
                    <a href="{{ route('about-us') }}" class="nav-item">About Us</a>
                    <a href="{{ route('contact') }}" class="nav-item">Contact</a>
                    
                    <div class="user-actions">
                        <a href="{{ route('troli.index') }}" class="cart-button">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
        <section class="pt-24 pb-20">
            <div class="container mx-auto px-6 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-brand-orange">Cerita Dibalik Snack Juara</h1>
                <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Menjaga cita rasa warisan Nusantara, satu gigitan demi satu gigitan.</p>
            </div>

            <div class="container mx-auto px-6 pt-16">
                <div id="accordion-gallery" class="accordion-container">
                    <div class="accordion-item">
                        <img src="{{ asset('images/about-us/proses-pembuatan.png') }}" alt="Proses pembuatan kue tradisional">
                        <h3 class="accordion-caption large">Proses Pembuatan Tradisional</h3>
                    </div>
                    <div class="accordion-item">
                        <img src="{{ asset('images/about-us/ragam-jajanan.png') }}" alt="Berbagai macam jajanan pasar">
                        <h3 class="accordion-caption large">Ragam Jajanan Pasar</h3>
                    </div>
                    <div class="accordion-item">
                        <img src="{{ asset('images/about-us/tangan-ahli.png') }}" alt="Tangan seorang pembuat kue">
                        <h3 class="accordion-caption large">Tangan Ahli Pembuat Kue</h3>
                    </div>
                    <div class="accordion-item">
                        <img src="{{ asset('images/about-us/suasana-pasar.png') }}" alt="Pasar tradisional Indonesia">
                        <h3 class="accordion-caption large">Suasana Pasar Tradisional</h3>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-20">
            <div class="container mx-auto px-6 text-center max-w-4xl">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Menghadirkan Cita Rasa Juara ke Depan Pintu Anda</h2>
                <p class="text-gray-600">
                    Berawal dari kecintaan kami terhadap kekayaan kuliner Indonesia, Snack Juara lahir dengan misi sederhana: memudahkan semua orang untuk menikmati kelezatan jajanan pasar otentik. Kami percaya setiap kue tradisional memiliki cerita dan kehangatan yang layak untuk terus dilestarikan.
                </p>
            </div>
        </section>
        
        <section class="py-20">
            <div class="container mx-auto px-6">
                {{-- AWAL PERUBAHAN --}}
                <div class="flex flex-col md:flex-row items-start gap-12">
                    <div class="md:w-1/2">
                        <div class="mini-accordion-container">
                            <div class="mini-accordion-item">
                                <img src="{{ asset('images/about-us/pembuat-kue-lokal.png') }}" alt="Pembuat kue lokal tersenyum">
                                <h3 class="accordion-caption small">Ibu Pembuat Klepon</h3>
                            </div>
                            <div class="mini-accordion-item">
                                <img src="{{ asset('images/about-us/bahan-berkualitas.png') }}" alt="Bahan baku berkualitas untuk kue">
                                <h3 class="accordion-caption small">Bahan Baku Terbaik</h3>
                            </div>
                            <div class="mini-accordion-item">
                                <img src="{{ asset('images/about-us/senyum-penjual.png') }}" alt="Penjual kue di pasar">
                                <h3 class="accordion-caption small">Mitra UMKM Kami</h3>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-1/2 text-center md:text-left">
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">Kami Memberdayakan Pembuat Kue Lokal</h2>
                        <p class="text-gray-600">
                            Setiap pembelian Anda tidak hanya memuaskan selera, tetapi juga secara langsung mendukung pertumbuhan ekonomi para pahlawan di balik lezatnya kue-kue kami. Bersama, kita menjaga agar tradisi kuliner tetap hidup.
                        </p>
                    </div>
                </div>
                {{-- AKHIR PERUBAHAN --}}
            </div>
        </section>

        <section class="bg-white py-20">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Tumbuh Bersama Meraih Kepercayaan Anda</h2>
                <p class="mt-4 text-lg text-gray-500 max-w-3xl mx-auto mb-16">Tiga pilar utama yang menjadi fondasi kami dalam melayani Anda.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div>
                        <div class="icon-box">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Resep Otentik</h3>
                        <p class="text-gray-600">Kami berkomitmen menjaga keaslian rasa dengan resep tradisional warisan generasi.</p>
                    </div>
                    <div>
                        <div class="icon-box">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Kualitas Terjamin</h3>
                        <p class="text-gray-600">Dari bahan baku hingga pengemasan, kami menerapkan standar kualitas yang ketat.</p>
                    </div>
                    <div>
                        <div class="icon-box">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8a1 1 0 001-1zM3 11h10" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Pelayanan Andal</h3>
                        <p class="text-gray-600">Sistem logistik kami dirancang untuk mengantar pesanan Anda dengan cepat dan aman.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-orange-200">
        <div class="container mx-auto px-6 py-8 text-center text-gray-500">
            <p>&copy; {{ date('Y') }} Snack Juara. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>