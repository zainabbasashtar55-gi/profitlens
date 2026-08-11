@extends('layouts.tenant')

@section('title', 'Customers')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Customers</h1>
            <p class="text-sm text-slate-600">{{ $customers->total() }} {{ Str::plural('customer', $customers->total()) }}</p>
        </div>
        <a href="{{ route('customers.create') }}" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Add customer</a>
    </div>

    <form method="GET" class="mb-4">
        <input name="q" value="{{ $search }}" placeholder="Search name, email or company…"
               class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm">
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Name</th>
                    <th class="text-left px-5 py-2">Company</th>
                    <th class="text-left px-5 py-2">Email</th>
                    <th class="text-right px-5 py-2">Sales</th>
                    <th class="text-right px-5 py-2">LTV</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $c)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3 font-medium">{{ $c->name }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $c->company ?: '—' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $c->email ?: '—' }}</td>
                        <td class="px-5 py-3 text-right text-slate-600">{{ $c->sales_count }}</td>
                        <td class="px-5 py-3 text-right font-mono">${{ number_format($c->lifetimeValue(), 2) }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3 text-xs">
                                <a href="{{ route('customers.edit', $c) }}" class="text-indigo-600 hover:underline">Edit</a>
                                @if (auth()->user()->hasAnyRole(['owner', 'admin']))
                                    <form method="POST" action="{{ route('customers.destroy', $c) }}" onsubmit="return confirm('Delete this customer?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                        No customers yet. <a href="{{ route('customers.create') }}" class="text-indigo-600">Add your first one</a>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>
@endsection
