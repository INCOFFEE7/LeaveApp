<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Halaman Kedaluwarsa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Tailwind CDN (opsional, kalau sudah ada di project bisa dihapus) --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="max-w-md w-full bg-white shadow-lg rounded-xl p-8 text-center">
        <div class="flex justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-yellow-500" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-semibold text-gray-800 mb-3">
            Halaman Kedaluwarsa
        </h1>

        <p class="text-gray-600 mb-6">
            Sesi Anda telah berakhir atau halaman ini sudah tidak berlaku.
            Silakan muat ulang halaman atau masuk kembali.
        </p>

        <div class="flex flex-col gap-3">
            <a href="{{ url()->previous() }}"
               class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Kembali
            </a>

            <a href="{{ route('login') }}"
               class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Login Ulang
            </a>
        </div>
    </div>

</body>
</html>
