@extends('layouts.tenant')

@section('title', 'Products')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Products</h1>
            <p class="text-sm text-slate-600">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>
        </div>
        <a href="{{ route('products.create') }}" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Add product</a>
    </div>

    <form method="GET" class="mb-4">
        <input name="q" value="{{ $search }}" placeholder="Search by name or SKU…" class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm">
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Name</th>
                    <th class="text-left px-5 py-2">SKU</th>
                    <th class="text-right px-5 py-2">Cost</th>
                    <th class="text-right px-5 py-2">Price</th>
                    <th class="text-right px-5 py-2">Margin</th>
                    <th class="text-center px-5 py-2">Active</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $p)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3 font-medium">{{ $p->name }}</td>
                        <td class="px-5 py-3 text-slate-500 font-mono text-xs">{{ $p->sku ?: '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono">${{ number_format($p->cost_price, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono">${{ number_format($p->sell_price, 2) }}</td>
                        <td class="px-5 py-3 text-right text-emerald-700 font-medium">{{ number_format($p->margin(), 1) }}%</td>
                        <td class="px-5 py-3 text-center">
                            @if ($p->active)
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                            @else
                                <span class="inline-block w-2 h-2 rounded-full bg-slate-300"></span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3 text-xs">
                                <a href="{{ route('products.edit', $p) }}" class="text-indigo-600 hover:underline">Edit</a>
                                @if (auth()->user()->hasAnyRole(['owner', 'admin']))
                                    <form method="POST" action="{{ route('products.destroy', $p) }}" onsubmit="return confirm('Delete this product?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
