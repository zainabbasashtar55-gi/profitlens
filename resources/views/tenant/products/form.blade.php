@extends('layouts.tenant')

@section('title', $product->exists ? 'Edit product' : 'New product')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold mb-1">{{ $product->exists ? 'Edit product' : 'New product' }}</h1>
        <p class="text-sm text-slate-600 mb-6">Cost price is captured at sale time, so future cost changes don't rewrite history.</p>

        @if ($errors->any())
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
                <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
              class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            @csrf
            @if ($product->exists) @method('PUT') @endif

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                    <input name="name" required value="{{ old('name', $product->name) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">SKU</label>
                    <input name="sku" value="{{ old('sku', $product->sku) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cost price *</label>
                    <input name="cost_price" type="number" step="0.01" min="0" required value="{{ old('cost_price', $product->cost_price) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sell price *</label>
                    <input name="sell_price" type="number" step="0.01" min="0" required value="{{ old('sell_price', $product->sell_price) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2">{{ old('description', $product->description) }}</textarea>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" {{ old('active', $product->active) ? 'checked' : '' }} class="rounded border-slate-300">
                Active (available for new sales)
            </label>

            <div class="flex gap-2 pt-2">
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">{{ $product->exists ? 'Save changes' : 'Create product' }}</button>
                <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm">Cancel</a>
            </div>
        </form>
    </div>
@endsection
