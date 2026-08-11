@extends('layouts.tenant')

@section('title', 'Reset password')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg border border-slate-200 p-8">
        <h1 class="text-2xl font-bold">Set a new password</h1>
        <p class="mt-1 text-sm text-slate-600">Pick something strong — at least 8 characters.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input name="email" type="email" required value="{{ old('email', $email) }}"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 bg-slate-50 text-slate-600">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                <input name="password" type="password" required minlength="8" autofocus
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
                <input name="password_confirmation" type="password" required minlength="8"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <button class="w-full px-4 py-2.5 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                Reset password
            </button>
        </form>
    </div>
@endsection
