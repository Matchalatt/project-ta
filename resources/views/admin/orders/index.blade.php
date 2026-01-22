@extends('layouts.admin')

@section('title', 'Manajemen Pesanan')

@section('header', 'Semua Pesanan Pelanggan')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail Pesanan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</div>
                            {{-- ====================================================== --}}
                            {{-- PEMBARUAN DI SINI: Tampilkan No. WhatsApp --}}
                            {{-- ====================================================== --}}
                            <div class="text-sm text-gray-500">{{ $order->customer_whatsapp ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <ul class="list-disc list-inside text-sm text-gray-700">
                                @foreach ($order->items as $item)
                                    <li>{{ $item->quantity }}x {{ $item->product_name }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                            Rp {{ number_format($order->total_price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $order->notes ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($order->status == 'paid')
                                <span class="text-xs font-medium bg-green-100 text-green-800 px-2 py-1 rounded-full">Lunas</span>
                            @elseif ($order->status == 'unpaid')
                                <span class="text-xs font-medium bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Belum Dibayar</span>
                            @elseif ($order->status == 'cancelled')
                                <span class="text-xs font-medium bg-red-100 text-red-800 px-2 py-1 rounded-full">Dibatalkan</span>
                            @else
                                <span class="text-xs font-medium bg-gray-100 text-gray-800 px-2 py-1 rounded-full">{{ ucfirst($order->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            Belum ada pesanan yang masuk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection