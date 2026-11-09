<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Newsroom Portal' }} - {{ config('app.name', 'Kisiitv News') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Playfair+Display:ital,wght@0,600;0,800;1,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-slate-50 flex flex-col justify-between">
    <!-- Top Global Editorial Bar -->
    <header class="bg-[#0b162c] text-white border-b-4 border-[#d9252a]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 text-white hover:opacity-90 transition">
                    <img src="{{ asset('images/kisii-tv-logo.png') }}" alt="Kisii TV" class="h-10 w-auto object-contain">
                    <div class="flex flex-col">
                        <span class="font-bold text-lg leading-tight uppercase font-headline text-white">Kisii TV</span>
                        <span class="text-[10px] text-blue-300 font-semibold tracking-wide">Amandegere Y'Omogusii</span>
                    </div>
                </a>
            </div>
            <div class="text-xs text-slate-300 hidden sm:flex items-center space-x-4">
                <span>{{ now()->timezone('Africa/Nairobi')->format('l, F j, Y') }}</span>
                <span>•</span>
                <span class="text-red-400 font-semibold uppercase tracking-wider">Editorial Network</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="w-full max-w-md">
            @if (session('status'))
                <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 text-sm font-medium">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-[#d9252a] text-red-800 text-sm">
                    <div class="font-bold mb-1">Please correct the following:</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white p-8 shadow-sm border border-slate-200">
                @yield('content')
            </div>

            <div class="text-center mt-6 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} Kisiitv News Organization. All rights reserved.</p>
                <p class="mt-1">Authorized editorial personnel & registered readers only.</p>
            </div>
        </div>
    </main>

    <!-- Subtle Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-center gap-6">
            <a href="{{ route('home') }}" class="hover:text-slate-900 transition">Front Page</a>
            <span class="text-slate-300">|</span>
            <span class="text-slate-600">Nairobi & Kisii Editorial Bureaus</span>
            <span class="text-slate-300">|</span>
            <span class="text-slate-400">Security Clearance Level 1</span>
        </div>
    </footer>
</body>
</html>
