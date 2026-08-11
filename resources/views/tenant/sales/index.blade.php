@extends('layouts.tenant')

@section('title', 'Sales')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Sales</h1>
            <p class="text-sm text-slate-600">{{ $sales->total() }} {{ Str::plural('sale', $sales->total()) }}</p>
        </div>
        <a href="{{ route('sales.create') }}" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Record sale</a>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-3 mb-4 flex gap-2">
        <select name="status" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All statuses</option>
            <option value="paid"     {{ ($filters['status'] ?? '') === 'paid'     ? 'selected' : '' }}>Paid</option>
            <option value="draft"    {{ ($filters['status'] ?? '') === 'draft'    ? 'selected' : '' }}>Draft</option>
            <option value="refunded" {{ ($filters['status'] ?? '') === 'refunded' ? 'selected' : '' }}>Refunded</option>
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
        <span class="self-center text-slate-500 text-sm">→</span>
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
        <button class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-sm">Filter</button>
        <a href="{{ route('sales.index') }}" class="px-3 py-1.5 rounded-md text-slate-500 text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Date</th>
                    <th class="text-left px-5 py-2">Customer</th>
                    <th class="text-left px-5 py-2">Status</th>
                    <th class="text-right px-5 py-2">Revenue</th>
                    <th class="text-right px-5 py-2">Profit</th>
                    <th class="text-right px-5 py-2">Margin</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $s)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3 text-slate-700">{{ $s->sale_date->format('M j, Y') }}</td>
                        <td class="px-5 py-3">{{ $s->customer?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @php $cls = ['paid' => 'bg-emerald-50 text-emerald-700', 'draft' => 'bg-amber-50 text-amber-700', 'refunded' => 'bg-rose-50 text-rose-700'][$s->status] ?? 'bg-slate-50'; @endphp
                            <span class="text-xs px-2 py-0.5 rounded {{ $cls }}">{{ $s->status }}</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono">${{ number_format($s->total_revenue, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono {{ $s->total_profit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">${{ number_format($s->total_profit, 2) }}</td>
                        <td class="px-5 py-3 text-right text-slate-600">{{ number_format($s->profitMargin(), 1) }}%</td>
                        <td class="px-5 py-3 text-right">
                            @if (auth()->user()->hasAnyRole(['owner', 'admin']))
                                <form method="POST" action="{{ route('sales.destroy', $s) }}" onsubmit="return confirm('Delete this sale?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 hover:underline text-xs">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">No sales yet. <a href="{{ route('sales.create') }}" class="text-indigo-600">Record your first sale</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $sales->links() }}</div>
@endsection
