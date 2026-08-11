@extends('layouts.tenant')

@section('title', $customer->exists ? 'Edit customer' : 'New customer')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold mb-1">{{ $customer->exists ? 'Edit customer' : 'New customer' }}</h1>
        <p class="text-sm text-slate-600 mb-6">Customer records feed into the analytics dashboard (LTV, top customers).</p>

        @if ($errors->any())
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
                <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}"
              class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            @csrf
            @if ($customer->exists) @method('PUT') @endif

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                    <input name="name" value="{{ old('name', $customer->name) }}" required class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company</label>
                    <input name="company" value="{{ old('company', $customer->company) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email', $customer->email) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2">{{ old('notes', $customer->notes) }}</textarea>
            </div>

            <div class="flex gap-2 pt-2">
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">{{ $customer->exists ? 'Save changes' : 'Create customer' }}</button>
                <a href="{{ route('customers.index') }}" class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm">Cancel</a>
            </div>
        </form>
    </div>
@endsection
