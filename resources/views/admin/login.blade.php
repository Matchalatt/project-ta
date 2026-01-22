<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    {{-- Memuat pustaka Tailwind CSS dari CDN untuk kemudahan --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">Admin Panel</h1>
            <p class="text-center text-gray-500 mb-8">Silakan masuk untuk melanjutkan</p>
            
            {{-- Form untuk Login --}}
            <form action="{{ route('admin.login.submit') }}" method="POST">
                {{-- Token CSRF untuk keamanan --}}
                @csrf

                {{-- Input Email --}}
                <div class="mb-5">
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-600">Email</label>
                    <input type="email" id="email" name="email"
                           class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition"
                           placeholder="contoh@domain.com" required>
                </div>

                {{-- Input Password --}}
                <div class="mb-6">
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-600">Password</label>
                    <input type="password" id="password" name="password"
                           class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition"
                           placeholder="•••••••••" required>
                </div>

                {{-- Tombol Login --}}
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75 transition duration-300 ease-in-out">
                    Login
                </button>
            </form>
        </div>
    </div>

</body>
</html>