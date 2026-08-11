@php
    $fmt0 = fn ($n) => '$' . number_format((float) $n, 0);
@endphp

<div data-goal-wrap data-goal-target="{{ $goalProgress['target'] ?? 0 }}">
    @if ($goalProgress)
        <div class="flex items-baseline justify-between mb-1">
            <div class="text-2xl font-bold" data-goal-current>{{ $fmt0($goalProgress['current']) }}</div>
            <div class="text-sm text-slate-500">of <span data-goal-target-label>{{ $fmt0($goalProgress['target']) }}</span></div>
        </div>
        <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
            <div data-goal-bar
                 class="h-full rounded-full transition-all duration-700 {{ $goalProgress['on_track'] ? 'bg-emerald-500' : 'bg-amber-500' }}"
                 style="width: {{ min(100, $goalProgress['pct']) }}%"></div>
        </div>
        <div class="mt-1.5 flex items-center justify-between text-xs">
            <span class="font-medium" data-goal-pct>{{ $goalProgress['pct'] }}%</span>
            <span class="text-slate-500">{{ $goalProgress['period_label'] }}</span>
        </div>
        <div class="mt-1 text-xs">
            <span data-goal-chip class="inline-block px-1.5 py-0.5 rounded {{ $goalProgress['on_track'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                {{ $goalProgress['on_track'] ? 'On track' : 'Behind pace' }}
            </span>
            <span class="text-slate-500" data-goal-remaining>{{ $fmt0($goalProgress['remaining']) }} to go</span>
        </div>
    @else
        <p class="text-sm text-slate-500 mb-3">No profit goal set for this month. Set one to track progress as sales come in.</p>
    @endif

    <form method="POST" action="{{ route('goals.store') }}" class="mt-3 flex gap-2">
        @csrf
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
            <input name="target_amount" type="number" step="1" min="0" required
                   value="{{ $goalProgress['target'] ?? '' }}"
                   placeholder="10000"
                   class="w-full rounded-md border border-slate-300 pl-6 pr-3 py-2 text-sm">
        </div>
        <button class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
            {{ $goalProgress ? 'Update' : 'Set goal' }}
        </button>
    </form>
    @error('target_amount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
</div>
