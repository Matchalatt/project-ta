<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->name }} - Snack Juara</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --brand-orange: #F97316;
            --brand-orange-light: #FFF7ED;
            --brand-orange-hover: #FFEDD5;
        }
        body { 
            font-family: 'Poppins', sans-serif; 
            color: #374151; 
            background-color: var(--brand-orange-light);
        }
        .header-custom { 
            background-color: #ffffff; 
            position: relative; 
            z-index: 10; 
            /* Menambahkan shadow halus agar ada pemisah dengan konten */
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
        .logout-button { width: 100%; text-align: left; padding: 12px 16px; font-size: 14px; color: #6B7280; background: none; border: none; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; }
        .logout-button:hover { background-color: var(--brand-orange); color: white; }
        #cart-count-badge { position: absolute; top: 2px; right: 2px; background-color: #EF4444; color: white; font-size: 11px; font-weight: 600; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transform: scale(0); transition: transform 0.2s ease-out; }
        #cart-count-badge.visible { transform: scale(1); }
    </style>
</head>
<body class="antialiased">

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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span id="cart-count-badge" class="{{ session('troli') && count(session('troli')) > 0 ? 'visible' : '' }}">
                                {{ session('troli') ? count(session('troli')) : 0 }}
                            </span>
                        </a>
                        <div class="profile-dropdown">
                            <button class="profile-button uppercase">{{ substr(auth()->user()->username, 0, 1) }}</button>
                            <div class="dropdown-menu">
                                <div class="dropdown-header">Halo, <span class="username">{{ auth()->user()->username }}</span></div>
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="logout-button">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                          <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V5.414l7.293 7.293a1 1 0 001.414-1.414L5.414 4H13a1 1 0 100-2H4a1 1 0 00-1 1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Logout</span>
                                    </button>
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
        
        </header>

    <main class="pt-28 pb-16">
        <div class="container mx-auto px-6">
            <div class="bg-white p-8 rounded-xl shadow-lg">
                <form action="{{ route('troli.add') }}" method="POST" id="add-to-cart-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    
                    <div class="flex flex-col lg:flex-row gap-10">
                        {{-- Sisi Kiri: Gambar Produk --}}
                        <div class="lg:w-1/3">
                            <div class="bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden h-96">
                                <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            </div>
                        </div>
                        
                        {{-- Sisi Kanan: Detail & Aksi --}}
                        <div class="lg:w-2/3 flex flex-col">
                            <h1 class="text-4xl font-bold text-gray-900 leading-tight">{{ $product->name }}</h1>
                            <p class="text-gray-600 mt-4 text-base">{{ $product->description }}</p>

                            @if($product->options->isNotEmpty())
                                <h4 class="text-md font-bold text-gray-800 mt-6 mb-3">Pilih Varian:</h4>
                                <div class="space-y-3">
                                    @foreach($product->options as $index => $option)
                                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-all duration-300 hover:bg-orange-200 has-[:checked]:bg-orange-200 has-[:checked]:border-orange-500 has-[:checked]:shadow-sm">
                                        <span class="font-semibold text-gray-700">{{ $option->name }}</span>
                                        <span class="font-bold text-lg text-orange-600">Rp {{ number_format($option->price, 0, ',', '.') }}</span>
                                        <input type="radio" name="product_option_id" value="{{ $option->id }}" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300" {{ $index === 0 ? 'checked' : '' }}>
                                    </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-6">
                                    <p class="text-3xl font-bold text-orange-600">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                </div>
                            @endif
                            
                            <div class="mt-auto pt-6">
                                <div class="flex items-center gap-8">
                                    <div>
                                        <label for="quantity" class="block text-sm font-bold text-gray-800 mb-2">Jumlah</label>
                                        <div class="flex items-center border border-gray-300 rounded-lg">
                                            <button type="button" id="decrease-qty" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-l-md">-</button>
                                            <input type="number" id="quantity" name="quantity" value="1" min="1" class="w-16 h-10 text-center border-l border-r focus:outline-none focus:ring-2 focus:ring-orange-400">
                                            <button type="button" id="increase-qty" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-r-md">+</button>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <label for="notes" class="block text-sm font-bold text-gray-800 mb-2">Catatan (Opsional)</label>
                                        <textarea id="notes" name="notes" rows="1" placeholder="Contoh: Jangan terlalu pedas" class="w-full border border-gray-300 rounded-lg p-2 h-10 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition-all"></textarea>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg transition-transform duration-200 ease-in-out hover:scale-[1.02] flex items-center justify-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                          <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                                        </svg>
                                        Masukkan Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            @if(isset($recommendations) && $recommendations->count() > 0)
            <div class="mt-16">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Mungkin Anda Juga Suka</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                    @foreach($recommendations as $rec_product)
                    <a href="{{ route('produk.show', $rec_product) }}" class="block bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                        <div class="overflow-hidden">
                            <img src="{{ asset('products/' . $rec_product->image) }}" alt="{{ $rec_product->name }}" class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 truncate">{{ $rec_product->name }}</h3>
                            <p class="text-orange-600 font-bold mt-1 text-lg">Rp {{ number_format($rec_product->price, 0, ',', '.') }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="container mx-auto px-6 py-6 text-center text-gray-500">
            <p>&copy; {{ date('Y') }} Snack Juara. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const quantityInput = document.getElementById('quantity');
        const decreaseBtn = document.getElementById('decrease-qty');
        const increaseBtn = document.getElementById('increase-qty');

        decreaseBtn.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value, 10);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
        increaseBtn.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value, 10);
            quantityInput.value = currentValue + 1;
        });

        const cartForm = document.getElementById('add-to-cart-form');
        cartForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(cartForm);
            const actionUrl = cartForm.action;
            const submitButton = cartForm.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton.innerHTML;

            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Menambahkan...
            `;
            submitButton.disabled = true;

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    const cartCountBadge = document.getElementById('cart-count-badge');
                    if (cartCountBadge && data.cartCount !== undefined) {
                        cartCountBadge.innerText = data.cartCount;
                        if (data.cartCount > 0) {
                            cartCountBadge.classList.add('visible');
                        } else {
                            cartCountBadge.classList.remove('visible');
                        }
                    }
                } else {
                    alert(data.message || 'Gagal menambahkan produk. Coba lagi.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan koneksi. Silakan coba lagi.');
            })
            .finally(() => {
                submitButton.innerHTML = originalButtonContent;
                submitButton.disabled = false;
            });
        });
    });
    </script>
</body>
</html>