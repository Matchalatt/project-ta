@extends('layouts.admin')

@section('title', 'Tambah Produk Baru')

@section('header', 'Tambah Produk Baru')

@section('content')
    {{-- Menampilkan pesan error jika validasi gagal --}}
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Oops! Terjadi kesalahan.</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
        @csrf
        
        {{-- NAMA PRODUK --}}
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-bold mb-2">Nama Produk:</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        {{-- HARGA UTAMA --}}
        <div class="mb-4">
            <label for="price" class="block text-gray-700 font-bold mb-2">Harga Utama:</label>
            <input type="number" name="price" id="price" value="{{ old('price') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required step="100">
            <p class="text-xs text-gray-500 mt-1">Harga ini akan digunakan jika produk tidak memiliki varian.</p>
        </div>

        {{-- DESKRIPSI PRODUK --}}
        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-bold mb-2">Deskripsi Produk:</label>
            <textarea name="description" id="description" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('description') }}</textarea>
        </div>
        
        {{-- KATEGORI & GAMBAR --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="category" class="block text-gray-700 font-bold mb-2">Kategori:</label>
                <select name="category" id="category" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Pilih Kategori</option>
                    <option value="jajanan" {{ old('category') == 'jajanan' ? 'selected' : '' }}>Jajanan</option>
                    <option value="paket" {{ old('category') == 'paket' ? 'selected' : '' }}>Paket</option>
                </select>
            </div>
            <div>
                <label for="image" class="block text-gray-700 font-bold mb-2">Gambar Produk:</label>
                <input type="file" name="image" id="image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
        </div>

        {{-- [TAMBAHAN] INPUT UNTUK TAGS --}}
        <div class="mb-4">
            <label for="tags" class="block text-gray-700 font-bold mb-2">Tags Produk:</label>
            <input type="text" name="tags" id="tags" value="{{ old('tags') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <p class="text-xs text-gray-500 mt-1">Ketik setiap tag lalu tekan Enter. Contoh: manis, gurih, gorengan, dll.</p>
        </div>
        {{-- [AKHIR TAMBAHAN] --}}

        <hr class="my-6">

        {{-- BAGIAN VARIAN PRODUK (DINAMIS) --}}
        <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Varian / Pilihan Produk (Opsional)</h3>
            <p class="text-sm text-gray-600 mb-4">Kosongkan jika produk ini tidak memiliki varian harga atau ukuran (cth: Pastel Kecil, Pastel Besar).</p>
            <div id="options-container">
                {{-- Form untuk varian akan ditambahkan di sini oleh JavaScript --}}
            </div>
            <button type="button" id="add-option-btn" class="mt-2 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                + Tambah Varian
            </button>
        </div>
        
        <div class="flex items-center justify-between mt-8">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Simpan Produk
            </button>
            <a href="{{ route('admin.products.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Batal
            </a>
        </div>
    </form>
@endsection

@push('scripts')
{{-- JAVASCRIPT untuk menambah varian secara dinamis --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('options-container');
        const addButton = document.getElementById('add-option-btn');
        let optionIndex = 0;

        addButton.addEventListener('click', function () {
            const newOption = document.createElement('div');
            newOption.classList.add('flex', 'items-center', 'gap-4', 'mb-2', 'p-2', 'border', 'rounded-md');
            newOption.innerHTML = `
                <div class="flex-1">
                    <label class="text-sm text-gray-600">Nama Varian (cth: Kecil)</label>
                    <input type="text" name="options[${optionIndex}][name]" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm" required>
                </div>
                <div class="flex-1">
                    <label class="text-sm text-gray-600">Harga Varian</label>
                    <input type="number" name="options[${optionIndex}][price]" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 text-sm" required step="100">
                </div>
                <button type="button" class="remove-option-btn bg-red-500 hover:bg-red-700 text-white font-bold p-2 rounded mt-5 text-xs">Hapus</button>
            `;
            container.appendChild(newOption);
            optionIndex++;
        });

        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-option-btn')) {
                e.target.parentElement.remove();
            }
        });
    });
</script>

{{-- [TAMBAHAN] JAVASCRIPT UNTUK TAGIFY --}}
<link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
<script src="https://unpkg.com/@yaireo/tagify"></script>
<script>
    var input = document.querySelector('input[name=tags]');
    new Tagify(input);
</script>
{{-- [AKHIR TAMBAHAN] --}}
@endpush