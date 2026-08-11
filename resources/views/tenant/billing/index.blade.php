@extends('layouts.tenant')

@section('title', 'Billing')

@php $fmt = fn ($n) => '$' . number_format($n / 100, 0); @endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Billing &amp; plan</h1>
        <p class="text-sm text-slate-600">Manage your workspace subscription. Billing is per workspace, charged monthly.</p>
    </div>

    {{-- Banner: dev mode if no Stripe keys --}}
    @if (! $stripeKey)
        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm mb-4">
            <strong>Dev mode:</strong> Stripe is not configured. Plan switches happen instantly without payment. Set <code class="bg-amber-100 px-1 rounded">STRIPE_KEY</code> + <code class="bg-amber-100 px-1 rounded">STRIPE_PRICE_PRO</code> in .env to enable real billing.
        </div>
    @endif

    @if (request('stripe') === 'success')
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm mb-4">
            🎉 Payment received. Your plan is now active.
        </div>
    @endif

    {{-- Current plan + usage --}}
    <section class="bg-white rounded-lg border border-slate-200 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Current plan</div>
                <div class="mt-1 text-3xl font-bold capitalize">{{ $currentPlan }}</div>
                <div class="text-sm text-slate-600">
                    @if ($currentPlan === 'free')
                        $0/mo — free forever
                    @else
                        {{ $fmt(config("plans.plans.{$currentPlan}.price_cents")) }}/mo · {{ $tenant->subscribed('default') ? 'active' : 'dev mode' }}
                    @endif
                </div>
            </div>
            <div class="text-right">
                @if ($tenant->hasStripeId() && config('cashier.key'))
                    <form action="{{ route('billing.portal') }}" method="POST">
                        @csrf
                        <button class="px-3 py-2 rounded-md border border-slate-300 bg-white text-sm">Stripe portal →</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Usage bars --}}
        <div class="grid md:grid-cols-2 gap-4 mt-4">
            @foreach ($summary['limits'] as $key => $check)
                @php
                    $label = match ($key) {
                        'users' => 'Team members',
                        'sales_per_month' => 'Sales this month',
                        'products' => 'Products in catalogue',
                        'storage_mb' => 'Receipt storage',
                        default => ucfirst($key),
                    };
                    $color = $check['percent_used'] >= 90 ? 'bg-rose-500' : ($check['percent_used'] >= 70 ? 'bg-amber-500' : 'bg-indigo-500');
                    $isUnlimited = $check['limit'] === PHP_INT_MAX;
                    $unit = $key === 'storage_mb' ? ' MB' : '';
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-slate-700">{{ $label }}</span>
                        <span class="text-slate-500">
                            {{ $check['current'] }}{{ $unit }} /
                            {{ $isUnlimited ? '∞' : $check['limit'] . $unit }}
                        </span>
                    </div>
                    <div class="h-2 rounded bg-slate-100 overflow-hidden">
                        <div class="h-full rounded {{ $color }}" style="width: {{ $isUnlimited ? 5 : $check['percent_used'] }}%"></div>
                    </div>
                    @if ($check['at_limit'])
                        <div class="text-xs text-rose-600 mt-1">Limit reached — upgrade to add more.</div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Plan picker --}}
    @if (session('status'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm mb-4">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <h2 class="text-lg font-semibold mb-3">Switch plan</h2>
    <div class="grid md:grid-cols-3 gap-4">
        @foreach ($plans as $slug => $p)
            @php $isCurrent = $slug === $currentPlan; $isHighlight = $p['highlight'] ?? false; @endphp
            <div class="bg-white rounded-lg p-6 border-2 {{ $isHighlight ? 'border-indigo-500' : 'border-slate-200' }} relative">
                @if ($isHighlight)
                    <span class="absolute -top-3 right-4 px-2 py-0.5 text-xs font-medium bg-indigo-600 text-white rounded">Popular</span>
                @endif
                <div class="text-sm font-semibold {{ $isHighlight ? 'text-indigo-600' : 'text-slate-500' }}">{{ $p['name'] }}</div>
                <div class="mt-2 text-3xl font-bold">
                    @if ($p['price_cents'] === 0) $0
                    @else {{ $fmt($p['price_cents']) }}<span class="text-sm font-medium text-slate-500">/mo</span>
                    @endif
                </div>
                <ul class="mt-4 text-sm text-slate-600 space-y-1.5">
                    @foreach ($p['features'] as $f)
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            {{ $f }}
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">
                    @if ($isCurrent)
                        <button disabled class="w-full px-4 py-2 rounded-md bg-slate-100 text-slate-500 text-sm cursor-default">Current plan</button>
                    @else
                        <form action="{{ route('billing.checkout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $slug }}">
                            <button class="w-full px-4 py-2 rounded-md {{ $isHighlight ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'border border-slate-300 hover:bg-slate-50' }} text-sm font-medium">
                                @if ($slug === 'free') Downgrade @else {{ $stripeKey ? 'Upgrade' : 'Switch' }} @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
