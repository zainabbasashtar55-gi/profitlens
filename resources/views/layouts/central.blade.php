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
                <span class="inline-block w-7 h-7 rounded bg-indigo-600"></span>
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
