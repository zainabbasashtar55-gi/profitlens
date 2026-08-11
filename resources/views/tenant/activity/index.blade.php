@extends('layouts.tenant')

@section('title', 'Activity log')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Activity log</h1>
        <p class="text-sm text-slate-600">Every change in this workspace is recorded here. Audit trail kept indefinitely.</p>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-3 mb-4 flex gap-2">
        <select name="type" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All event types</option>
            <option value="Sale"     {{ ($filters['type'] ?? '') === 'Sale'     ? 'selected' : '' }}>Sales</option>
            <option value="Expense"  {{ ($filters['type'] ?? '') === 'Expense'  ? 'selected' : '' }}>Expenses</option>
            <option value="Customer" {{ ($filters['type'] ?? '') === 'Customer' ? 'selected' : '' }}>Customers</option>
            <option value="Product"  {{ ($filters['type'] ?? '') === 'Product'  ? 'selected' : '' }}>Products</option>
            <option value="User"     {{ ($filters['type'] ?? '') === 'User'     ? 'selected' : '' }}>Users</option>
        </select>
        <button class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-sm">Filter</button>
        <a href="{{ route('activity.index') }}" class="px-3 py-1.5 rounded-md text-slate-500 text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        @forelse ($activities as $a)
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
            <div class="px-5 py-4 border-b border-slate-100 last:border-b-0 flex items-start gap-4">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full flex-shrink-0 font-semibold {{ $iconCls }}">{{ $icon }}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm">
                        <span class="font-medium">{{ $a->causer?->name ?? 'System' }}</span>
                        <span class="text-slate-600">{{ $a->description }}</span>
                    </div>
                    @if ($a->properties->isNotEmpty() && $a->event === 'updated')
                        @php $old = $a->properties->get('old', []); $new = $a->properties->get('attributes', []); @endphp
                        @if (!empty($new))
                            <details class="mt-1 text-xs text-slate-500">
                                <summary class="cursor-pointer hover:text-slate-700">View changes</summary>
                                <table class="mt-2 ml-1 text-xs">
                                    @foreach ($new as $key => $val)
                                        <tr>
                                            <td class="pr-3 font-mono text-slate-500">{{ $key }}:</td>
                                            <td class="text-rose-600 line-through">{{ is_array($old[$key] ?? null) ? json_encode($old[$key]) : ($old[$key] ?? '∅') }}</td>
                                            <td class="px-2 text-slate-400">→</td>
                                            <td class="text-emerald-700">{{ is_array($val) ? json_encode($val) : $val }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </details>
                        @endif
                    @endif
                    <div class="text-xs text-slate-400 mt-0.5">
                        {{ $a->created_at->diffForHumans() }}
                        <span class="text-slate-300">·</span>
                        {{ $a->created_at->format('M j, Y g:i A') }}
                        @if ($a->subject_type)
                            <span class="text-slate-300">·</span>
                            <span class="text-slate-400">{{ class_basename($a->subject_type) }}#{{ $a->subject_id }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-sm text-slate-500">
                No activity yet. Once you record sales, log expenses, or invite teammates, you'll see a full history here.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
@endsection
