<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Pemesanan - Snack Juara</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-orange-500">Snack Juara</a>
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-gray-600 hover:text-orange-500">Kembali ke Dashboard</a>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Histori Pemesanan Anda</h1>

        {{-- PEMBARUAN: Tempat untuk menampilkan notifikasi sukses --}}
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="space-y-4 p-5">
                @forelse ($orders as $order)
                <div class="border rounded-lg p-4">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <p class="font-bold text-lg text-orange-600">{{ $order->order_code }}</p>
                            <p class="text-sm text-gray-500">Tanggal: {{ $order->created_at->format('d M Y, H:i') }}</p>
                            <p class="font-semibold mt-1">Total: Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            {{-- Status Pesanan --}}
                            @if ($order->status == 'unpaid')
                                <span class="text-sm font-medium bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full">Menunggu Pembayaran</span>
                                <a href="{{ route('checkout.repay', $order->order_code) }}" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">
                                    Bayar Sekarang
                                </a>
                            @elseif ($order->status == 'paid')
                                <span class="text-sm font-medium bg-green-100 text-green-800 px-3 py-1 rounded-full">Pembayaran Berhasil</span>
                            @elseif ($order->status == 'cancelled')
                                 <span class="text-sm font-medium bg-red-100 text-red-800 px-3 py-1 rounded-full">Dibatalkan</span>
                            @else
                                 <span class="text-sm font-medium bg-gray-100 text-gray-800 px-3 py-1 rounded-full">{{ ucfirst($order->status) }}</span>
                            @endif

                            {{-- PEMBARUAN: Form & Tombol Hapus --}}
                            <form action="{{ route('order.destroy', $order) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Hapus Pesanan">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="border-t my-4"></div>
                    <div class="space-y-3">
                        @foreach ($order->items as $item)
                        <div class="flex items-center gap-4">
                            <img src="{{ asset('products/' . ($item->product->image ?? 'default.jpg')) }}" alt="{{ $item->product_name }}" class="w-24 h-24 object-cover rounded-md bg-gray-200">
                            <div class="flex-grow">
                                <p class="font-semibold text-gray-800">{{ $item->product_name }} @if($item->option_name) ({{ $item->option_name }}) @endif</p>
                                <p class="text-sm text-gray-500">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="border-t mt-4 pt-4 text-sm text-gray-600 space-y-2">
                        @if (!empty($order->notes))
                            <p><strong>Catatan:</strong> {{ $order->notes }}</p>
                        @endif

                        @if ($order->delivery_method == 'pickup')
                            <div>
                                <strong>Metode:</strong> Ambil di Tempat. 
                                <a href="https://maps.app.goo.gl/vRtN6j9Ry36FPtyW8" target="_blank" class="text-blue-600 hover:underline">(Lihat Lokasi)</a>
                            </div>
                        @elseif ($order->delivery_method == 'delivery')
                            <div>
                                <p><strong>Metode:</strong> Diantar oleh Penjual.</p>
                                <p><strong>Alamat:</strong>
                                    @if(Illuminate\Support\Str::startsWith($order->delivery_address, ['http://', 'https://']))
                                        <a href="{{ $order->delivery_address }}" target="_blank" class="text-blue-600 hover:underline">{{ $order->delivery_address }}</a>
                                    @else
                                        {{ $order->delivery_address }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">Anda belum memiliki riwayat pemesanan.</p>
                    <a href="{{ route('dashboard') }}" class="mt-4 inline-block bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-5 rounded-lg transition-colors">
                        Mulai Belanja
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </main>
</body>
</html>