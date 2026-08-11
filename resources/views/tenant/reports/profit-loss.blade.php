@extends('layouts.tenant')

@section('title', 'Profit & Loss')

@php $fmt = fn ($n) => '$' . number_format((float) $n, 2); @endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Profit &amp; Loss</h1>
            <p class="text-sm text-slate-600">{{ \Carbon\Carbon::parse($pl['period']['from'])->format('M j, Y') }} – {{ \Carbon\Carbon::parse($pl['period']['to'])->format('M j, Y') }}</p>
        </div>
        <form method="GET" class="flex gap-2 items-center text-sm">
            <input type="date" name="from" value="{{ $from }}" class="rounded-md border border-slate-300 px-3 py-1.5">
            <span class="text-slate-500">→</span>
            <input type="date" name="to" value="{{ $to }}" class="rounded-md border border-slate-300 px-3 py-1.5">
            <button class="px-3 py-1.5 rounded-md bg-slate-900 text-white">Update</button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden max-w-3xl">
        <table class="w-full text-sm">
            <tbody>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <td class="px-6 py-3 font-semibold">Revenue</td>
                    <td class="px-6 py-3 text-right font-mono font-semibold">{{ $fmt($pl['revenue']) }}</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="px-6 py-2 pl-10 text-slate-600">Cost of goods sold</td>
                    <td class="px-6 py-2 text-right font-mono text-slate-600">({{ $fmt($pl['cogs']) }})</td>
                </tr>
                <tr class="border-b border-slate-200 bg-emerald-50/50">
                    <td class="px-6 py-3 font-semibold">Gross profit</td>
                    <td class="px-6 py-3 text-right font-mono font-semibold text-emerald-700">
                        {{ $fmt($pl['gross_profit']) }}
                        <span class="ml-2 text-xs text-slate-500">{{ $pl['gross_margin_pct'] }}% margin</span>
                    </td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="px-6 py-3 font-semibold">Operating expenses</td>
                    <td></td>
                </tr>
                @forelse ($pl['operating_expenses'] as $line)
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-2 pl-10 text-slate-600">{{ $line['category'] }}</td>
                        <td class="px-6 py-2 text-right font-mono text-slate-600">({{ $fmt($line['total']) }})</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-6 py-3 pl-10 text-slate-400 italic">No operating expenses in this period.</td></tr>
                @endforelse
                <tr class="border-b border-slate-200 bg-slate-50">
                    <td class="px-6 py-3 font-medium">Total expenses</td>
                    <td class="px-6 py-3 text-right font-mono font-medium">({{ $fmt($pl['total_expenses']) }})</td>
                </tr>
                <tr class="border-t-2 border-slate-300 {{ $pl['net_profit'] >= 0 ? 'bg-emerald-50' : 'bg-rose-50' }}">
                    <td class="px-6 py-4 font-bold text-lg">Net profit</td>
                    <td class="px-6 py-4 text-right font-mono font-bold text-lg {{ $pl['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $fmt($pl['net_profit']) }}
                        <span class="ml-2 text-xs text-slate-500">{{ $pl['net_margin_pct'] }}% margin</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex gap-2">
        <a href="{{ route('reports.sales.csv') }}" class="px-3 py-2 rounded-md border border-slate-300 bg-white text-sm">↓ Sales CSV</a>
        <a href="{{ route('reports.expenses.csv') }}" class="px-3 py-2 rounded-md border border-slate-300 bg-white text-sm">↓ Expenses CSV</a>
        <button onclick="window.print()" class="px-3 py-2 rounded-md border border-slate-300 bg-white text-sm">🖨 Print</button>
    </div>
@endsection
