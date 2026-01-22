<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Snack Juara</title>
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Script untuk Midtrans Snap --}}
    <script type="text/javascript"
      src="https://app.sandbox.midtrans.com/snap/snap.js"
      data-client-key="{{ config('services.midtrans.client_key') }}"></script>
      
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100">

    <header class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-orange-500">Snack Juara</a>
            {{-- Tombol kembali yang dinamis --}}
            @if ($order)
                <a href="{{ route('order.history') }}" class="text-sm font-semibold text-gray-600 hover:text-orange-500">← Kembali ke Histori</a>
            @else
                <a href="{{ route('troli.index') }}" class="text-sm font-semibold text-gray-600 hover:text-orange-500">← Kembali ke Troli</a>
            @endif
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        {{-- Judul halaman yang dinamis --}}
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            @if ($order)
                Lanjutkan Pembayaran Pesanan
            @else
                Selesaikan Pesanan Anda
            @endif
        </h1>
        
        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Bagian Kiri: Detail Pesanan --}}
            <div class="w-full lg:w-3/5">
                <div class="bg-white rounded-lg shadow-md p-6">
                    
                    {{-- Tampilkan form lengkap HANYA untuk checkout baru --}}
                    @if (!$order)
                    <form id="checkout-form">
                        @csrf
                        <h2 class="text-xl font-bold border-b pb-4 mb-4">Detail Pesanan</h2>
                        <div class="space-y-4 mb-8">
                            @php $totalPrice = 0; @endphp
                            @forelse ($checkoutItems as $id => $item)
                            <div class="flex items-center gap-4">
                                <img src="{{ asset('products/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-16 h-16 object-cover rounded-md">
                                <div class="flex-grow">
                                    <p class="font-semibold">{{ $item['name'] }} @if($item['option_name']) ({{ $item['option_name'] }}) @endif</p>
                                    <p class="text-sm text-gray-500">{{ $item['quantity'] }} x Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                </div>
                                <p class="font-semibold">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</p>
                            </div>
                            @php $totalPrice += $item['price'] * $item['quantity']; @endphp
                            @empty
                            <p class="text-gray-500">Tidak ada item untuk di-checkout.</p>
                            @endforelse
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total Pembayaran</span>
                                <span class="text-orange-500">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="total_price" value="{{ $totalPrice }}">

                        <h2 class="text-xl font-bold border-b pb-4 my-6">Data Penerima</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" id="customer_name" name="customer_name" value="{{ auth()->user()->username }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" required>
                            </div>
                            <div>
                                <label for="customer_whatsapp" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                                <input type="tel" id="customer_whatsapp" name="customer_whatsapp" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="08123456789" required>
                            </div>
                        </div>

                        <h2 class="text-xl font-bold border-b pb-4 my-6">Metode Pengambilan</h2>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 border rounded-md has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500">
                                <input type="radio" name="delivery_method" value="pickup" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300" checked>
                                <span class="ml-3 text-sm font-medium text-gray-900">Ambil di Tempat (Pick Up)</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-md has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500">
                                <input type="radio" name="delivery_method" value="delivery" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300">
                                <span class="ml-3 text-sm font-medium text-gray-900">Diantar oleh Penjual</span>
                            </label>
                        </div>

                        <div id="pickup-info" class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 fade-in">
                            <p class="font-semibold">Lokasi Pengambilan:</p>
                            <a href="https://maps.app.goo.gl/vRtN6j9Ry36FPtyW8" target="_blank" class="text-blue-600 hover:underline">
                                Jl. Plamongansari Kp. Kedung RT03/RW12 (Klik untuk membuka di Google Maps)
                            </a>
                        </div>
                        <div id="delivery-info" class="mt-4 space-y-2 hidden fade-in">
                            <label for="delivery_address" class="block text-sm font-medium text-gray-700">Link Lokasi / Alamat Lengkap</label>
                            <textarea id="delivery_address" name="delivery_address" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Contoh: https://maps.app.goo.gl/... atau Jl. Anggrek No. 5, RT 01/RW 02, Kel. ..."></textarea>
                        </div>

                        <h2 class="text-xl font-bold border-b pb-4 my-6">Catatan Tambahan</h2>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan untuk Penjual</label>
                            <textarea id="notes" name="notes" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Contoh: Pesanan untuk hari Sabtu. Ambil jam 2 siang."></textarea>
                        </div>
                    </form>
                    
                    {{-- Tampilkan ringkasan data HANYA untuk pembayaran ulang --}}
                    @else
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Ringkasan Pesanan</h2>
                        <div class="space-y-2 text-gray-700">
                            <p><strong>Kode Pesanan:</strong> <span class="font-mono text-orange-600">{{ $order->order_code }}</span></p>
                            <p><strong>Nama Penerima:</strong> {{ $order->customer_name }}</p>
                            <p><strong>WhatsApp:</strong> {{ $order->customer_whatsapp }}</p>
                            <p><strong>Metode:</strong> {{ $order->delivery_method == 'pickup' ? 'Ambil di Tempat' : 'Diantar' }}</p>
                            @if($order->delivery_method == 'delivery')
                            <p><strong>Alamat:</strong> {{ $order->delivery_address }}</p>
                            @endif
                        </div>
                         <div class="border-t my-4"></div>
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Detail Item</h2>
                        <div class="space-y-4">
                            @foreach ($checkoutItems as $item)
                                <div class="flex items-center gap-4">
                                    {{-- ====================================================== --}}
                                    {{-- PEMBARUAN DI SINI --}}
                                    {{-- Menggunakan path gambar dinamis dari data yang dikirim controller --}}
                                    {{-- Ini akan menampilkan 'default.jpg' yang dikirim dari controller repay --}}
                                    <img src="{{ asset('products/' . ($item['image'] ?? 'default.jpg')) }}" alt="{{ $item['name'] }}" class="w-16 h-16 object-cover rounded-md bg-gray-200">
                                    {{-- ====================================================== --}}
                                    <div class="flex-grow">
                                        <p class="font-semibold">{{ $item['name'] }} @if($item['option_name']) ({{ $item['option_name'] }}) @endif</p>
                                        <p class="text-sm text-gray-500">{{ $item['quantity'] }} x Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                    </div>
                                    <p class="font-semibold">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bagian Kanan: Ringkasan Pembayaran --}}
            <div class="w-full lg:w-2/5">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h2 class="text-xl font-bold text-gray-800 border-b pb-4">Ringkasan Pembayaran</h2>
                    <div class="my-4 flex justify-between font-bold text-xl text-gray-800">
                        <span>Total</span>
                        <span class="text-orange-500">Rp {{ number_format($order ? $order->total_price : ($totalPrice ?? 0), 0, ',', '.') }}</span>
                    </div>

                    {{-- Tampilkan tombol yang sesuai --}}
                    @if ($order)
                        <button id="repay-button" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                            Bayar Sekarang
                        </button>
                    @else
                        <button type="submit" form="checkout-form" id="pay-button" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg transition-colors disabled:bg-gray-400">
                            Lanjutkan ke Pembayaran
                        </button>
                    @endif
                    
                    <p id="payment-status" class="text-center text-sm text-gray-500 mt-2"></p>
                </div>
            </div>
        </div>
    </main>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- LOGIKA FORM UNTUK CHECKOUT BARU (JIKA ADA) ---
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            const deliveryRadios = document.querySelectorAll('input[name="delivery_method"]');
            const pickupInfo = document.getElementById('pickup-info');
            const deliveryInfo = document.getElementById('delivery-info');
            const deliveryAddressInput = document.getElementById('delivery_address');

            function toggleDeliveryInfo() {
                const selectedMethod = document.querySelector('input[name="delivery_method"]:checked').value;
                if (selectedMethod === 'pickup') {
                    pickupInfo.style.display = 'block';
                    deliveryInfo.style.display = 'none';
                    deliveryAddressInput.required = false;
                } else {
                    pickupInfo.style.display = 'none';
                    deliveryInfo.style.display = 'block';
                    deliveryAddressInput.required = true;
                }
            }
            deliveryRadios.forEach(radio => radio.addEventListener('change', toggleDeliveryInfo));
            toggleDeliveryInfo(); // Panggil saat halaman dimuat
        }

        // --- LOGIKA BARU UNTUK PROSES PEMBAYARAN ---
        const payButton = document.getElementById('pay-button');
        const repayButton = document.getElementById('repay-button');
        const paymentStatus = document.getElementById('payment-status');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Fungsi umum untuk menangani popup pembayaran
        function handlePayment(snapToken) {
            window.snap.pay(snapToken, {
                onSuccess: function(result){
                    paymentStatus.textContent = 'Pembayaran Berhasil! Mengalihkan...';
                    window.location.href = '{{ route("order.history") }}';
                },
                onPending: function(result){
                    paymentStatus.textContent = 'Menunggu Pembayaran. Mengalihkan...';
                    window.location.href = '{{ route("order.history") }}';
                },
                onError: function(result){
                    paymentStatus.textContent = 'Pembayaran Gagal. Coba lagi.';
                    if (payButton) payButton.disabled = false;
                    if (repayButton) repayButton.disabled = false;
                },
                onClose: function(){
                    // Mengarahkan ke histori agar pengguna bisa mencoba bayar ulang.
                    window.location.href = '{{ route("order.history") }}';
                }
            });
        }

        // Event listener untuk tombol checkout BARU
        if (payButton) {
            checkoutForm.addEventListener('submit', function (event) {
                event.preventDefault();
                payButton.disabled = true;
                paymentStatus.textContent = 'Membuat pesanan dan memuat pembayaran...';

                fetch('{{ route("checkout.process") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: new FormData(checkoutForm)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        paymentStatus.textContent = data.error;
                        payButton.disabled = false;
                        return;
                    }
                    if (data.snap_token) {
                        handlePayment(data.snap_token);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    paymentStatus.textContent = 'Terjadi kesalahan jaringan.';
                    payButton.disabled = false;
                });
            });
        }
        
        // Event listener untuk tombol BAYAR ULANG
        if (repayButton) {
            repayButton.addEventListener('click', function() {
                this.disabled = true;
                paymentStatus.textContent = 'Memuat sesi pembayaran...';
                
                // Ambil token yang sudah dikirim oleh Controller
                const snapToken = '{{ $snapToken ?? "" }}';

                if (snapToken) {
                    // Jika token ada, langsung panggil fungsi pembayaran
                    handlePayment(snapToken);
                } else {
                    // Jika token kosong (karena error di controller), tampilkan pesan
                    paymentStatus.textContent = 'Gagal memuat sesi pembayaran. Silakan muat ulang halaman.';
                    this.disabled = false;
                }
            });
        }
    });
    </script>
</body>
</html>