@extends('layouts.admin')

@section('title', 'Manajemen Produk')

@section('header', 'Manajemen Produk')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div></div>
        <a href="{{ route('admin.products.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            + Tambah Produk
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Gambar</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Produk</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kategori</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tags</th> {{-- <-- [TAMBAHAN] Kolom header baru --}}
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Harga</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <img src="{{ asset('products/' . $product->image) }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover rounded">
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap">{{ $product->name }}</p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                            <span aria-hidden class="absolute inset-0 {{ $product->category == 'jajanan' ? 'bg-green-200' : 'bg-yellow-200' }} opacity-50 rounded-full"></span>
                            <span class="relative">{{ ucfirst($product->category) }}</span>
                        </span>
                    </td>

                    {{-- [TAMBAHAN] Kolom untuk menampilkan tags --}}
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <div class="flex flex-wrap gap-1">
                            @if($product->tags)
                                @foreach(explode(',', $product->tags) as $tag)
                                    <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-2 py-1 rounded-full">
                                        {{ trim($tag) }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </div>
                    </td>
                    {{-- [AKHIR TAMBAHAN] --}}

                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <div class="flex items-center space-x-2">
                            <form action="{{ route('admin.products.toggleStatus', $product->id) }}" method="POST">
                                @csrf
                                @if ($product->is_available)
                                    <button type="submit" class="text-white bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded text-xs font-bold" title="Tandai sebagai habis">
                                        Habis
                                    </button>
                                @else
                                    <button type="submit" class="text-white bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-xs font-bold" title="Tandai sebagai tersedia">
                                        Tersedia
                                    </button>
                                @endif
                            </form>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk \'{{ $product->name }}\'?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-bold" title="Hapus Produk">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-10"> {{-- <-- Ubah colspan menjadi 6 --}}
                        <p class="text-gray-500">Belum ada produk.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection