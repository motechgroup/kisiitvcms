@extends('layouts.auth')

@section('content')
<div class="mb-6">
    <div class="inline-block px-2 py-0.5 text-[10px] font-bold tracking-widest uppercase bg-slate-900 text-white mb-2">Account Recovery</div>
    <h1 class="text-2xl font-bold text-slate-950 uppercase tracking-tight" style="font-family: 'Playfair Display', serif;">Reset Password</h1>
    <p class="text-xs text-slate-500 mt-1">Enter your registered email address and we will dispatch a secure reset link.</p>
</div>

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf

    <div>
        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Email Address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
            class="w-full px-3 py-2.5 text-sm bg-white border border-slate-300 focus:outline-none focus:border-[#d9252a] focus:ring-1 focus:ring-[#d9252a] transition"
            placeholder="reporter@kisiitv.com">
    </div>

    <div class="pt-2">
        <button type="submit"
            class="w-full py-3 px-4 bg-[#d9252a] hover:bg-[#a00d24] text-white font-bold uppercase text-xs tracking-widest transition duration-150 ease-in-out shadow-sm cursor-pointer">
            Send Reset Instructions
        </button>
    </div>

    <div class="pt-4 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-600">
            Remember your credentials?
            <a href="{{ route('login') }}" class="font-bold text-[#d9252a] hover:underline">Back to sign in</a>
        </p>
    </div>
</form>
@endsection
