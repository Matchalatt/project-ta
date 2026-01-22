<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Keranjang Belanja - Snack Juara</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    {{-- Header Sederhana --}}
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-orange-500">Snack Juara</a>
            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-orange-500">&larr; Lanjut Belanja</a>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Keranjang Belanja Anda</h1>
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- Daftar Item Keranjang --}}
            <div class="w-full lg:w-2/3">
                <div class="bg-white rounded-lg shadow-md">
                    @forelse ($cart as $id => $item)
                    <div class="flex items-start p-4 border-b last:border-b-0">
                        {{-- Checkbox --}}
                        <div class="flex-shrink-0 pt-10">
                             @php
                                 $checkoutId = isset($item['db_id']) ? $item['db_id'] : $id;
                             @endphp
                             <input type="checkbox" class="item-checkbox h-5 w-5 text-orange-500 border-gray-300 rounded focus:ring-orange-500 cursor-pointer"
                                   data-price="{{ $item['price'] }}" 
                                   data-quantity="{{ $item['quantity'] }}"
                                   data-id="{{ $checkoutId }}"
                                   checked>
                        </div>

                        {{-- Gambar & Detail Produk --}}
                        <div class="flex-grow flex gap-4 ml-4">
                            <img src="{{ asset('products/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-24 h-24 object-cover rounded-md">
                            <div class="flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800">{{ $item['name'] }}</h3>
                                    @if($item['option_name'])
                                    <p class="text-sm text-gray-500">Varian: {{ $item['option_name'] }}</p>
                                    @endif
                                    @if($item['notes'])
                                    <p class="text-sm text-gray-500 mt-1">Catatan: <span class="italic">"{{ $item['notes'] }}"</span></p>
                                    @endif
                                    <p class="text-gray-700 mt-1">Jumlah: {{ $item['quantity'] }}</p>
                                </div>
                                <p class="font-bold text-orange-500 text-md">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</p>
                            </div>
                        </div>

                        {{-- Tombol Hapus --}}
                        <div class="flex-shrink-0">
                            @php
                                $removeId = isset($item['db_id']) ? $item['db_id'] : $id;
                            @endphp
                             <form action="{{ route('troli.remove', $removeId) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="p-6 text-center text-gray-500">
                        <p>Keranjang Anda masih kosong. Yuk, mulai belanja!</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Ringkasan Pesanan diubah menjadi Form Checkout --}}
            <div class="w-full lg:w-1/3">
                <form action="{{ route('checkout.show') }}" method="POST" id="checkout-form">
                    @csrf
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-4">Ringkasan Pesanan</h2>
                        
                        <div id="checkout-items-container"></div>
                        
                        <div class="py-4 space-y-2">
                            <div class="flex justify-between text-gray-700">
                                <span>Subtotal</span>
                                <span id="subtotal-price">Rp 0</span>
                            </div>
                        </div>
                        <div class="flex justify-between font-bold text-xl text-gray-800 border-t pt-4">
                            <span>Total</span>
                            <span id="total-price" class="text-orange-500">Rp 0</span>
                        </div>

                        <button type="submit" class="w-full mt-6 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg transition-colors disabled:bg-gray-400" id="checkout-btn">
                            Checkout
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
    
    {{-- ====================================================================== --}}
    {{-- [PEMBARUAN] Bagian Rekomendasi Hybrid (k-NN / TF-IDF) --}}
    {{-- ====================================================================== --}}
    @if(isset($recommendations) && $recommendations->isNotEmpty())
    <section class="container mx-auto px-6 py-8">
        <div class="border-t pt-8">
            
            {{-- [PEMBARUAN] Judul dinamis --}}
            <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $recommendationTitle }}</h2>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                
                {{-- [PEMBARUAN] Loop menggunakan $recommendations --}}
                @foreach ($recommendations as $product)
                <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                    <a href="{{ route('produk.show', $product->id) }}" class="block">
                        <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-40 object-cover group-hover:opacity-80 transition-opacity">
                    </a>
                    <div class="p-4">
                        <h3 class="text-md font-semibold text-gray-800 truncate">
                            <a href="{{ route('produk.show', $product->id) }}" class="hover:text-orange-500">{{ $product->name }}</a>
                        </h3>
                        <p class="text-lg font-bold text-orange-500 mt-2">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        
                        <button 
                            onclick="addToCartFromRec({{ $product->id }})"
                            class="w-full mt-4 bg-gray-200 hover:bg-orange-500 hover:text-white text-gray-700 font-bold py-2 px-3 rounded-lg transition-colors text-sm">
                            + Keranjang
                        </button>
                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </section>
    @endif
    {{-- ====================================================================== --}}
    
    
    {{-- Script JavaScript untuk menangani pemilihan item checkout --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const subtotalPriceEl = document.getElementById('subtotal-price');
            const totalPriceEl = document.getElementById('total-price');
            const checkoutBtn = document.getElementById('checkout-btn');
            const checkoutItemsContainer = document.getElementById('checkout-items-container');

            function calculateTotal() {
                let total = 0;
                let itemsSelected = 0;
                checkoutItemsContainer.innerHTML = '';

                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const price = parseFloat(checkbox.dataset.price);
                        const quantity = parseInt(checkbox.dataset.quantity, 10);
                        total += price * quantity;
                        itemsSelected++;
                        
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'items[]';
                        hiddenInput.value = checkbox.dataset.id;
                        checkoutItemsContainer.appendChild(hiddenInput);
                    }
                });

                const formattedTotal = 'Rp ' + total.toLocaleString('id-ID');
                subtotalPriceEl.textContent = formattedTotal;
                totalPriceEl.textContent = formattedTotal;
                checkoutBtn.disabled = itemsSelected === 0;
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', calculateTotal);
            });
            
            calculateTotal();
        });

        // [PEMBARUAN] Fungsi untuk tombol tambah keranjang dari bagian rekomendasi.
        // (Kode ini tidak berubah dari file Anda sebelumnya)
        function addToCartFromRec(productId) {
            fetch('{{ route("troli.add") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Produk ditambahkan ke keranjang!');
                    // Reload halaman untuk memperbarui tampilan keranjang dan rekomendasi
                    window.location.reload(); 
                } else {
                    alert('Gagal menambahkan produk. Coba lagi.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Gagal menambahkan produk.');
            });
        }
    </script>

</body>
</html>