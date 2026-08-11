@extends('layouts.central')

@section('title', 'Sign up')

@section('content')
    <div class="max-w-xl mx-auto bg-white rounded-lg border border-slate-200 p-8">
        <h1 class="text-2xl font-bold">Create your workspace</h1>
        <p class="mt-1 text-sm text-slate-600">Each company gets a private subdomain and database. You become the workspace owner.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('signup') }}" class="mt-6 space-y-5">
            @csrf

            <fieldset class="space-y-4">
                <legend class="text-xs uppercase tracking-wide font-semibold text-slate-500">Workspace</legend>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company name</label>
                    <input name="name" type="text" required value="{{ old('name') }}" placeholder="Acme, Inc."
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Subdomain</label>
                    <div class="flex rounded-md border border-slate-300 overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                        <input name="subdomain" type="text" required value="{{ old('subdomain') }}" placeholder="acme"
                               class="flex-1 px-3 py-2 focus:outline-none">
                        <span class="px-3 py-2 bg-slate-100 text-slate-600 text-sm border-l border-slate-300">.{{ config('app.tenant_domain') }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Lowercase letters, numbers, and dashes only.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Plan</label>
                    @php $selectedPlan = old('plan', $prefillPlan ?? 'free'); @endphp
                    <select name="plan" class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="free"       {{ $selectedPlan === 'free' ? 'selected' : '' }}>Free — $0/mo · 3 users, 100 sales/mo</option>
                        <option value="pro"        {{ $selectedPlan === 'pro' ? 'selected' : '' }}>Pro — $29/mo · 25 users, 10K sales/mo</option>
                        <option value="enterprise" {{ $selectedPlan === 'enterprise' ? 'selected' : '' }}>Enterprise — Talk to sales</option>
                    </select>
                    @if (! config('cashier.key') && in_array($selectedPlan, ['pro', 'enterprise']))
                        <p class="mt-1 text-xs text-amber-700">
                            Dev mode: paid plans are activated instantly without payment. Set <code class="bg-amber-100 px-1 rounded">STRIPE_KEY</code> in .env to enable real billing.
                        </p>
                    @endif
                </div>
            </fieldset>

            <fieldset class="space-y-4 pt-4 border-t border-slate-200">
                <legend class="text-xs uppercase tracking-wide font-semibold text-slate-500">Owner account</legend>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Your name</label>
                    <input name="owner_name" type="text" required value="{{ old('owner_name') }}" placeholder="Jane Doe"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input name="owner_email" type="email" required value="{{ old('owner_email') }}" placeholder="jane@acme.com"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input name="owner_password" type="password" required minlength="8"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-slate-500">At least 8 characters.</p>
                </div>
            </fieldset>

            <button type="submit" class="w-full px-4 py-2.5 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                Create workspace
            </button>

            <p class="text-xs text-slate-500 text-center">
                Already have a workspace? Visit <code class="bg-slate-100 px-1 rounded">yourcompany.{{ config('app.tenant_domain') }}</code> to log in.
            </p>
        </form>
    </div>
@endsection
