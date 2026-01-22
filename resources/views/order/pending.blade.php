<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menunggu Pembayaran - Snack Juara</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md text-center max-w-md w-full">
        <svg class="w-16 h-16 mx-auto text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <h1 class="text-2xl font-bold text-gray-800 mt-4">Menunggu Pembayaran</h1>
        <p class="text-gray-600 mt-2">Pesanan Anda telah kami terima. Segera selesaikan pembayaran agar pesanan dapat kami proses.</p>
        <div class="text-left bg-gray-50 p-4 rounded-md mt-6">
            <p class="text-sm text-gray-500">Nomor Pesanan</p>
            <p class="font-semibold text-lg text-orange-600">{{ $order->order_code }}</p>
            <p class="text-sm text-gray-500 mt-2">Total Pembayaran</p>
            <p class="font-semibold text-lg">Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
        </div>
         <a href="{{ route('dashboard') }}" class="inline-block mt-6 bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>