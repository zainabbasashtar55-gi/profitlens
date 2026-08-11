@extends('layouts.tenant')

@section('title', $user->name)

@php
    $fmt = fn ($n) => '$' . number_format($n, 2);
    $roleClass = fn ($role) => match ($role) {
        'owner'  => 'bg-rose-50 text-rose-700 border-rose-200',
        'admin'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'member' => 'bg-slate-50 text-slate-700 border-slate-200',
        default  => 'bg-slate-50 text-slate-700 border-slate-200',
    };
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('team.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to team</a>
    </div>

    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                @if ($user->id === auth()->id())
                    <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600">you</span>
                @endif
                @foreach ($user->roles as $role)
                    <span class="text-xs px-2 py-0.5 rounded border {{ $roleClass($role->name) }}">{{ $role->name }}</span>
                @endforeach
            </div>
            <p class="mt-1 text-sm text-slate-600">{{ $user->email }}</p>
            <p class="mt-1 text-xs text-slate-500">
                Joined {{ $user->created_at->format('M j, Y') }} ·
                @if ($user->isOnline())
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Online now</span>
                @elseif ($user->last_seen_at)
                    Last seen {{ $user->last_seen_at->diffForHumans() }}
                    @if ($user->last_seen_ip) · from {{ $user->last_seen_ip }} @endif
                @else
                    Never logged in
                @endif
                @if ($user->hasVerifiedEmail())
                    · <span class="text-emerald-600">Email verified</span>
                @else
                    · <span class="text-amber-600">Email unverified</span>
                @endif
            </p>
        </div>

        @if ($canManage)
            <div class="flex gap-2">
                <form method="POST" action="{{ route('team.destroy', $user) }}"
                      onsubmit="return confirm('Remove {{ $user->name }} from this workspace?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-2 rounded-md border border-rose-300 text-rose-700 text-sm hover:bg-rose-50">Remove from workspace</button>
                </form>
            </div>
        @endif
    </div>

    {{-- Contribution stats --}}
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Sales recorded</div>
            <div class="mt-2 text-2xl font-bold">{{ $salesCount }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Revenue generated</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700">{{ $fmt($salesRevenue) }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Expenses logged</div>
            <div class="mt-2 text-2xl font-bold">{{ $expensesCount }}</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Total expense value</div>
            <div class="mt-2 text-2xl font-bold text-rose-700">{{ $fmt($expensesTotal) }}</div>
        </div>
    </div>

    {{-- Recent activity --}}
    <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-semibold">Recent activity by {{ $user->name }}</h2>
            <a href="{{ route('activity.index', ['user_id' => $user->id]) }}" class="text-xs text-indigo-600 hover:underline">Full audit trail →</a>
        </header>

        @if ($activities->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-slate-500">
                No activity yet from this user.
            </div>
        @else
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($activities as $a)
                    @php
                        $iconCls = match ($a->event) {
                            'created' => 'bg-emerald-100 text-emerald-700',
                            'updated' => 'bg-sky-100 text-sky-700',
                            'deleted' => 'bg-rose-100 text-rose-700',
                            default   => 'bg-slate-100 text-slate-700',
                        };
                        $icon = match ($a->event) {
                            'created' => '+',
                            'updated' => '✎',
                            'deleted' => '✕',
                            default   => '●',
                        };
                    @endphp
                    <li class="px-5 py-3 flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full {{ $iconCls }} font-semibold text-xs flex-shrink-0">{{ $icon }}</span>
                        <div class="flex-1">
                            <div class="text-sm text-slate-700">{{ $a->description }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ $a->created_at->diffForHumans() }} ·
                                <span class="text-slate-400">{{ $a->created_at->format('M j, Y g:i A') }}</span>
                                @if ($a->subject_type)
                                    · {{ class_basename($a->subject_type) }}@if ($a->subject_id)#{{ $a->subject_id }}@endif
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
