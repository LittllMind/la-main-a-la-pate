<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Vinyles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('head')
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

    <!-- Navigation Admin -->
    <nav class="bg-gray-800 text-white">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="/dashboard" class="text-xl font-bold">🎧 Admin Vinyles</a>
                <div class="flex items-center gap-4">
                    <a href="/kiosque" class="hover:text-gray-300">Kiosque</a>
                    <a href="/" class="hover:text-gray-300">Site</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="container mx-auto px-4 py-8">
        @if (session('success'))
            <div class="bg-green-500 text-white px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-500 text-white px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
