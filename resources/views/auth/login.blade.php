@extends('layouts.auth')

@section('content')
<div class="mb-6">
    <div class="inline-block px-2 py-0.5 text-[10px] font-bold tracking-widest uppercase bg-slate-900 text-white mb-2">Staff & Reader Access</div>
    <h1 class="text-2xl font-bold text-slate-950 uppercase tracking-tight" style="font-family: 'Playfair Display', serif;">Sign In to Kisiitv</h1>
    <p class="text-xs text-slate-500 mt-1">Access the digital newsroom, editorial desk, or subscriber portal.</p>
</div>

<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Email Address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
            class="w-full px-3 py-2.5 text-sm bg-white border border-slate-300 focus:outline-none focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a] transition"
            placeholder="reporter@kisiitv.com">
    </div>

    <div>
        <div class="flex items-center justify-between mb-1">
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Password</label>
            <a href="{{ route('password.request') }}" class="text-xs text-[#d9252a] hover:underline">Forgot password?</a>
        </div>
        <input id="password" type="password" name="password" required autocomplete="current-password"
            class="w-full px-3 py-2.5 text-sm bg-white border border-slate-300 focus:outline-none focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a] transition"
            placeholder="••••••••">
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center space-x-2 text-xs text-slate-600 cursor-pointer">
            <input type="checkbox" name="remember" class="border-slate-300 text-[#d9252a] focus:ring-[#d9252a]">
            <span>Keep me logged in</span>
        </label>
    </div>

    <div>
        <button type="submit"
            class="w-full py-3 px-4 bg-[#d9252a] hover:bg-[#a00d24] text-white font-bold uppercase text-xs tracking-widest transition duration-150 ease-in-out shadow-sm cursor-pointer">
            Sign In to Newsroom
        </button>
    </div>

    <div class="pt-4 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-600">
            Do not have a reader account?
            <a href="{{ route('register') }}" class="font-bold text-[#d9252a] hover:underline">Create account</a>
        </p>
    </div>
</form>

<div class="mt-6 pt-4 border-t border-dashed border-slate-200">
    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Pre-seeded Test Credentials:</div>
    <div class="bg-slate-50 p-2.5 text-xs text-slate-600 space-y-1 font-mono">
        <div><span class="text-slate-900 font-semibold">Super Admin:</span> superadmin@kisiitv.com</div>
        <div><span class="text-slate-900 font-semibold">Editor-in-Chief:</span> editor@kisiitv.com</div>
        <div><span class="text-slate-900 font-semibold">Politics Editor:</span> politics.editor@kisiitv.com</div>
        <div><span class="text-slate-900 font-semibold">Reporter:</span> reporter@kisiitv.com</div>
        <div class="text-slate-400">Password for all: <span class="text-slate-700">Password123!</span></div>
    </div>
</div>
@endsection
