@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('header')
    @auth('admin')
        Selamat Datang, {{ Auth::guard('admin')->user()->name }}!
    @endauth
@endsection

@section('content')
    {{-- ====================================================== --}}
    {{-- PEMBARUAN: Kartu Statistik Dinamis --}}
    {{-- ====================================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700">Total Pengguna</h3>
            <p class="text-4xl font-bold text-gray-900 mt-2">{{ $totalUsers }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700">Total Penjualan</h3>
            <p class="text-4xl font-bold text-gray-900 mt-2">Rp {{ number_format($totalSales, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700">Pesanan Belum Dibayar</h3>
            <p class="text-4xl font-bold text-gray-900 mt-2">{{ $newOrders }}</p>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- PEMBARUAN: Tabel Aktivitas Terbaru Dinamis --}}
    {{-- ====================================================== --}}
    <div class="mt-8 bg-white rounded-lg shadow">
        <h3 class="text-xl font-semibold text-gray-800 p-6">Aktivitas Pesanan Terbaru</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktivitas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($recentActivities as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $activity->customer_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                Memesan {{ $activity->items->count() }} jenis produk
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($activity->status == 'paid')
                                    <span class="text-xs font-medium bg-green-100 text-green-800 px-2 py-1 rounded-full">Lunas</span>
                                @elseif ($activity->status == 'unpaid')
                                    <span class="text-xs font-medium bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Belum Dibayar</span>
                                @else
                                    <span class="text-xs font-medium bg-gray-100 text-gray-800 px-2 py-1 rounded-full">{{ ucfirst($activity->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $activity->created_at->format('d M Y, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                Belum ada aktivitas pesanan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection