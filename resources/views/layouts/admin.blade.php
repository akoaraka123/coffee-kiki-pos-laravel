<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-gray-100">

<div class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="hidden md:flex w-64 bg-[#1c1c1c] text-white flex-col p-6">
        <h2 class="text-xl font-bold mb-8">Coffee Kiosk</h2>

        <nav class="space-y-4">
            <a href="#" class="block hover:text-gray-300">Dashboard</a>
            <a href="#" class="block hover:text-gray-300">Products</a>
            <a href="#" class="block hover:text-gray-300">Sales</a>
            <a href="#" class="block hover:text-gray-300">Reports</a>
        </nav>
    </aside>

    {{-- Content --}}
    <main class="flex-1 p-6">
        @yield('content')
    </main>

</div>

</body>
</html>
