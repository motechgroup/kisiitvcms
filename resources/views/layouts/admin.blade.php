<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Newsroom Management System') - Kisii TV</title>

    <!-- Favicon & Mobile Touch Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ \App\Models\SiteSetting::get('site_favicon') ?: asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ \App\Models\SiteSetting::get('site_favicon') ?: asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ \App\Models\SiteSetting::get('site_favicon') ?: asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#0a1c3d">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800|playfair-display:700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans-body antialiased text-slate-800" x-data="{ sidebarOpen: false }">

    <div class="min-h-full flex flex-col lg:flex-row">

        <!-- ========================================== -->
        <!-- SIDEBAR (KISII TV MIDNIGHT NAVY)           -->
        <!-- ========================================== -->
        <aside class="lg:w-64 bg-[#0a1c3d] text-white flex-shrink-0 flex flex-col justify-between border-r border-slate-800">
            <div>
                <!-- Brand Header -->
                <div class="h-16 px-6 bg-[#071329] flex items-center justify-between border-b border-slate-800">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3">
                        <img src="{{ asset('images/kisii-tv-logo.png') }}" alt="Kisii TV" class="h-9 w-auto">
                        <div class="flex flex-col">
                            <span class="text-sm font-black tracking-tight text-white font-headline">KISII<span class="text-[#d9252a]">TV</span></span>
                            <span class="text-[9px] font-bold text-blue-300 tracking-widest uppercase">Newsroom CMS</span>
                        </div>
                    </a>
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="p-4 space-y-1 text-xs font-semibold" :class="{ 'block': sidebarOpen, 'hidden lg:block': !sidebarOpen }">
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider px-3 pt-3 pb-1">
                        Executive Desk
                    </div>
                    
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded transition {{ request()->routeIs('admin.dashboard') ? 'bg-[#1b52bc] text-white font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="flex-grow">Super Admin Overview</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    </a>

                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider px-3 pt-4 pb-1">
                        Editorial & Publishing
                    </div>

                    <a href="{{ route('admin.articles.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded transition {{ request()->routeIs('admin.articles.*') ? 'bg-[#1b52bc] text-white font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        <span class="flex-grow">Articles &amp; Stories</span>
                        <span class="bg-blue-900 text-blue-200 text-[9px] font-bold px-1.5 py-0.5 rounded">CMS</span>
                    </a>

                    <a href="{{ route('admin.categories.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded transition {{ request()->routeIs('admin.categories.*') ? 'bg-[#1b52bc] text-white font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <span class="flex-grow">Categories &amp; Menu</span>
                        <span class="bg-[#d9252a] text-white text-[9px] font-black px-1.5 py-0.5 rounded">Nav</span>
                    </a>

                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider px-3 pt-4 pb-1">
                        Administration &amp; System
                    </div>

                    <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded transition {{ request()->routeIs('admin.users.*') ? 'bg-[#1b52bc] text-white font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span class="flex-grow">Newsroom Staff &amp; Users</span>
                    </a>

                    <a href="{{ route('admin.settings.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded transition {{ request()->routeIs('admin.settings.*') ? 'bg-[#1b52bc] text-white font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="flex-grow">Site Settings &amp; Theme</span>
                        <span class="bg-amber-400 text-slate-950 text-[9px] font-black px-1.5 py-0.5 rounded">Setup</span>
                    <a href="{{ route('admin.profile.edit') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded transition {{ request()->routeIs('admin.profile.*') ? 'bg-[#1b52bc] text-white font-bold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span class="flex-grow">My Profile &amp; Account</span>
                    </a>

                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider px-3 pt-4 pb-1">
                        Public Portal
                    </div>

                    <a href="{{ route('home') }}" target="_blank" class="flex items-center space-x-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded transition">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>View Live Broadcast</span>
                    </a>
                </nav>
            </div>

            <!-- User Status in Sidebar Footer -->
            <div class="p-4 bg-[#071329] border-t border-slate-800 flex items-center justify-between text-xs">
                <a href="{{ route('admin.profile.edit') }}" class="flex items-center space-x-2.5 min-w-0 hover:opacity-80 transition group" title="Open Profile Settings">
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}"
                        class="w-8 h-8 rounded-full object-cover border border-slate-600 shrink-0">
                    <div class="min-w-0">
                        <div class="font-bold text-white group-hover:text-blue-400 transition truncate">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-blue-300 font-mono truncate">{{ '@' . auth()->user()->username }}</div>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Log Out" class="text-slate-400 hover:text-[#d9252a] p-1.5 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ========================================== -->
        <!-- MAIN CONTENT WORKSPACE                     -->
        <!-- ========================================== -->
        <div class="flex-grow flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-extrabold text-slate-900 font-headline">
                        @yield('page_title', 'Editorial Newsroom')
                    </h1>
                </div>

                <div class="flex items-center space-x-4 text-xs">
                    <a href="{{ route('home') }}" target="_blank" class="px-3 py-1.5 bg-[#1b52bc] hover:bg-blue-800 text-white font-bold rounded uppercase tracking-wider transition">
                        Live Preview &rarr;
                    </a>
                </div>
            </header>

            <!-- Main Body -->
            <main class="flex-grow p-6 lg:p-8 max-w-7xl w-full mx-auto">
                <!-- Flash Alerts -->
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 text-xs flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-[#d9252a] text-red-900 text-xs flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-[#d9252a]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-[#d9252a] text-red-900 text-xs">
                        <div class="font-bold mb-1">Please correct the following errors:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

    </div>

</body>
</html>
