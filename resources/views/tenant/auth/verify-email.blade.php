@extends('layouts.tenant')

@section('title', 'Verify your email')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg border border-slate-200 p-8">
        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold">Verify your email</h1>
        <p class="mt-1 text-sm text-slate-600">
            We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Click it to confirm this is your address.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 flex gap-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    Resend verification email
                </button>
            </form>
            <form method="POST" action="{{ route('tenant.logout') }}">
                @csrf
                <button class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm hover:bg-slate-50">
                    Log out
                </button>
            </form>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Tip: in this dev environment, emails go to <code class="bg-slate-100 px-1 rounded">storage/logs/laravel.log</code>. Search for the verify URL there.
        </p>
    </div>
@endsection
