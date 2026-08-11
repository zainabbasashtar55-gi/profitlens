@extends('layouts.tenant')

@section('title', 'Accept invitation')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg border border-slate-200 p-8">
        <h1 class="text-2xl font-bold">Join {{ tenant('name') }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            You've been invited as a <strong>{{ $invitation->role }}</strong>.
            Set up your account to accept.
        </p>

        @if ($errors->any())
            <div class="mt-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" value="{{ $invitation->email }}" disabled
                       class="w-full rounded-md border border-slate-300 px-3 py-2 bg-slate-100 text-slate-600">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Your name</label>
                <input name="name" type="text" required value="{{ old('name') }}" autofocus
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input name="password" type="password" required minlength="8"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                <input name="password_confirmation" type="password" required minlength="8"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <button type="submit" class="w-full px-4 py-2.5 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                Accept &amp; create account
            </button>

            <p class="text-xs text-slate-500 text-center">
                Invitation expires {{ $invitation->expires_at->diffForHumans() }}.
            </p>
        </form>
    </div>
@endsection
