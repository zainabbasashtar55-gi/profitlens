@extends('layouts.tenant')

@section('title', 'Expenses')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Expenses</h1>
            <p class="text-sm text-slate-600">{{ $expenses->total() }} {{ Str::plural('expense', $expenses->total()) }}</p>
        </div>
        <a href="{{ route('expenses.create') }}" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Log expense</a>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-3 mb-4 flex gap-2">
        <select name="category_id" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search vendor or description…" class="flex-1 max-w-sm rounded-md border border-slate-300 px-3 py-1.5 text-sm">
        <button class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-sm">Filter</button>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Date</th>
                    <th class="text-left px-5 py-2">Description</th>
                    <th class="text-left px-5 py-2">Category</th>
                    <th class="text-left px-5 py-2">Vendor</th>
                    <th class="text-right px-5 py-2">Amount</th>
                    <th class="text-center px-5 py-2">Receipt</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $e)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3">{{ $e->expense_date->format('M j, Y') }}</td>
                        <td class="px-5 py-3 font-medium">
                            {{ $e->description }}
                            @if ($e->recurring)
                                <span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">↻ {{ $e->recurring_period }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if ($e->category)
                                <span class="inline-flex items-center gap-1 text-xs">
                                    <span class="inline-block w-2 h-2 rounded-full" style="background:{{ $e->category->color }}"></span>
                                    {{ $e->category->name }}
                                </span>
                            @else — @endif
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $e->vendor ?: '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono">${{ number_format($e->amount, 2) }}</td>
                        <td class="px-5 py-3 text-center">
                            @if ($e->receipt_path)
                                <a href="{{ $e->receiptUrl() }}" target="_blank" class="text-indigo-600 text-xs hover:underline">View</a>
                            @else <span class="text-slate-300">—</span> @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3 text-xs">
                                <a href="{{ route('expenses.edit', $e) }}" class="text-indigo-600 hover:underline">Edit</a>
                                @if (auth()->user()->hasAnyRole(['owner', 'admin']))
                                    <form method="POST" action="{{ route('expenses.destroy', $e) }}" onsubmit="return confirm('Delete this expense?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">No expenses logged yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>
@endsection
