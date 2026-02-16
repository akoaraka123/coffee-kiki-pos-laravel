<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-[#1c1c1c] text-white">

<div class="min-h-screen flex flex-col">

    {{-- Header --}}
    <div class="bg-black p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">Coffee Kiosk POS</h1>
        <span>{{ auth()->user()->name }}</span>
    </div>

    {{-- POS Content --}}
    <div class="flex-1 p-4">
        @yield('content')
    </div>

</div>

</body>
</html>
