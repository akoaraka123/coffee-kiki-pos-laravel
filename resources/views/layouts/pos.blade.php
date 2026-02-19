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
    <div class="bg-[#6b4a2b] p-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="grid h-11 w-11 place-items-center rounded-2xl bg-white/10 border border-white/20">
                <span class="text-xl leading-none">☕</span>
            </div>
            <div>
                <h1 class="text-xl font-bold leading-tight">GenSan Coffee Shop</h1>
                <div class="text-xs text-white/80">Staff: {{ auth()->user()->name }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15">
                Logout
            </button>
        </form>
    </div>

    {{-- POS Content --}}
    <div class="flex-1 p-4">
        @yield('content')
    </div>

</div>

</body>
</html>
