@extends('layouts.tenant')

@section('title', $expense->exists ? 'Edit expense' : 'New expense')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold mb-1">{{ $expense->exists ? 'Edit expense' : 'Log a new expense' }}</h1>
        <p class="text-sm text-slate-600 mb-6">Receipts up to 5 MB (PDF, JPG, PNG, WebP). Files are stored per-tenant — never visible across workspaces.</p>

        @if ($errors->any())
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
                <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}"
              enctype="multipart/form-data"
              class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            @csrf
            @if ($expense->exists) @method('PUT') @endif

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description *</label>
                    <input name="description" required value="{{ old('description', $expense->description) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vendor</label>
                    <input name="vendor" value="{{ old('vendor', $expense->vendor) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Amount *</label>
                    <input name="amount" type="number" step="0.01" min="0" required value="{{ old('amount', $expense->amount) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date *</label>
                    <input name="expense_date" type="date" required
                           min="{{ now()->subYears(5)->toDateString() }}"
                           max="{{ now()->toDateString() }}"
                           value="{{ old('expense_date', $expense->expense_date?->toDateString() ?? now()->toDateString()) }}"
                           class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select name="expense_category_id" class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="">— uncategorized —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Receipt</label>
                @if ($expense->receipt_path)
                    <div class="text-xs text-slate-500 mb-1">Current: <a href="{{ $expense->receiptUrl() }}" target="_blank" class="text-indigo-600">{{ $expense->receipt_original_name }}</a> — uploading a new one replaces it.</div>
                @endif
                <input name="receipt" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm">
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="recurring" value="0">
                <input type="checkbox" name="recurring" value="1" {{ old('recurring', $expense->recurring) ? 'checked' : '' }} class="rounded border-slate-300" onchange="document.getElementById('rp').classList.toggle('hidden', !this.checked)">
                Recurring expense
            </label>
            <div id="rp" class="{{ old('recurring', $expense->recurring) ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-slate-700 mb-1">Period</label>
                <select name="recurring_period" class="w-40 rounded-md border border-slate-300 px-3 py-2">
                    <option value="monthly" {{ old('recurring_period', $expense->recurring_period) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly"  {{ old('recurring_period', $expense->recurring_period) === 'yearly'  ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>

            <div class="flex gap-2 pt-2">
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">{{ $expense->exists ? 'Save changes' : 'Log expense' }}</button>
                <a href="{{ route('expenses.index') }}" class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm">Cancel</a>
            </div>
        </form>
    </div>
@endsection
