<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProfitLens') · {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <style>html, body { font-family: 'Inter', system-ui, sans-serif; }</style>
    @include('layouts._profitlens-theme')
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 @yield('body-class')">
    <nav class="bg-white border-b border-slate-200 @yield('nav-class')">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-2 font-semibold text-lg">
                <span class="pl-brandmark inline-grid w-8 h-8" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs><linearGradient id="profitlens-logo" x1="4" y1="3" x2="28" y2="29"><stop stop-color="#55DCFF"/><stop offset=".48" stop-color="#8B7CFF"/><stop offset="1" stop-color="#5B35E8"/></linearGradient></defs>
                        <rect x="2" y="2" width="28" height="28" rx="9" fill="url(#profitlens-logo)"/>
                        <path d="M8.5 21.5V16.8M13.4 21.5V12.7M18.3 21.5V15.1M23.2 21.5V9.5" stroke="white" stroke-width="2.15" stroke-linecap="round"/>
                        <path d="M8.5 13.2L13.4 9.8L18.3 11.5L23.2 6.9" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" opacity=".92"/>
                    </svg>
                </span>
                ProfitLens
            </a>
            <div class="flex items-center gap-6 text-sm">
                <a href="{{ route('signup') }}" class="text-slate-600 hover:text-slate-900">Sign up</a>
                <a href="{{ route('billing') }}" class="text-slate-600 hover:text-slate-900">Billing</a>
                <a href="https://github.com/" class="hidden md:inline text-slate-600 hover:text-slate-900">Docs</a>
            </div>
        </div>
    </nav>

    @if (session('status'))
        <div class="max-w-6xl mx-auto px-6 pt-6">
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                {{ session('status') }}
            </div>
        </div>
    @endif

    <main class="@yield('main-class', 'max-w-6xl mx-auto px-6 py-10')">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white mt-16">
        <div class="max-w-6xl mx-auto px-6 py-6 text-xs text-slate-500 flex justify-between">
            <span>&copy; {{ date('Y') }} ProfitLens</span>
            <span>Multi-tenant SaaS scaffolded with Laravel + stancl/tenancy</span>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
