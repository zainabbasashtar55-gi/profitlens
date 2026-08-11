@extends('layouts.tenant')

@section('title', 'Team')

@php
    $roleClass = fn ($role) => match ($role) {
        'owner'  => 'bg-rose-50 text-rose-700 border-rose-200',
        'admin'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'member' => 'bg-slate-50 text-slate-700 border-slate-200',
        default  => 'bg-slate-50 text-slate-700 border-slate-200',
    };
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Team</h1>
            <p class="text-sm text-slate-600">{{ $users->count() }} {{ Str::plural('member', $users->count()) }} · {{ $pendingInvitations->count() }} pending {{ Str::plural('invitation', $pendingInvitations->count()) }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm mb-4">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" class="mb-4">
        <input name="q" value="{{ $search }}" placeholder="Search name or email…"
               class="w-full max-w-sm rounded-md border border-slate-300 px-3 py-2 text-sm">
    </form>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold">Members</h2>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-2">Name</th>
                        <th class="text-left px-5 py-2">Status</th>
                        <th class="text-left px-5 py-2 w-44">Role</th>
                        <th class="text-right px-5 py-2 w-32"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3">
                                <a href="{{ route('team.show', $u) }}" class="block hover:bg-slate-50 -m-3 p-3 rounded">
                                    <div class="font-medium text-indigo-700 hover:underline">
                                        {{ $u->name }}
                                        @if ($u->id === auth()->id())<span class="ml-1 text-xs text-slate-400">(you)</span>@endif
                                    </div>
                                    <div class="text-xs text-slate-500">{{ $u->email }}</div>
                                </a>
                            </td>
                            <td class="px-5 py-3 text-xs">
                                @if ($u->isOnline())
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span class="text-emerald-700 font-medium">Online now</span>
                                    </span>
                                @elseif ($u->last_seen_at)
                                    <span class="text-slate-500">Last seen {{ $u->last_seen_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-slate-400 italic">Never logged in</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @php $userRole = $u->roles->first()?->name ?? 'member'; @endphp
                                @if (auth()->user()->hasAnyRole(['owner', 'admin']) && $u->id !== auth()->id() && (auth()->user()->hasRole('owner') || ! $u->hasRole('owner')))
                                    <form method="POST" action="{{ route('team.update-role', $u) }}" class="flex gap-1">
                                        @csrf @method('PATCH')
                                        <select name="role" onchange="if(confirm('Change role to ' + this.value + '?')) this.form.submit(); else this.value='{{ $userRole }}';"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                            @foreach ($availableRoles as $role)
                                                <option value="{{ $role }}" {{ $userRole === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="inline-block text-xs px-2 py-0.5 rounded border {{ $roleClass($userRole) }}">{{ $userRole }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($u->id !== auth()->id() && auth()->user()->hasAnyRole(['owner', 'admin']) && (auth()->user()->hasRole('owner') || ! $u->hasRole('owner')))
                                    <form method="POST" action="{{ route('team.destroy', $u) }}" onsubmit="return confirm('Remove {{ $u->name }}? Their account will be deactivated.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline text-xs">Remove</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-slate-500">No matches.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold">Invite</h2>
            </header>
            @can('invite-users')
                <div class="p-4">
                    <form method="POST" action="{{ route('invitations.store') }}" class="space-y-3">
                        @csrf
                        <input name="email" type="email" required placeholder="teammate@email.com"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <select name="role" class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button class="w-full px-3 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Send invite</button>
                    </form>
                </div>
            @else
                <p class="p-4 text-sm text-slate-500">You don't have permission to invite users.</p>
            @endcan

            {{-- Transfer ownership widget — only visible to the current owner --}}
            @if (auth()->user()->hasRole('owner'))
                <details class="border-t border-slate-200 group">
                    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Transfer ownership
                        <span class="text-xs text-slate-400 group-open:hidden">▸</span>
                    </summary>
                    <form method="POST" action="" id="transferForm" class="px-4 pb-4 space-y-3"
                          onsubmit="if(!this.action){ alert('Pick a teammate first'); return false; } return confirm('Are you sure? You will become an admin.');">
                        @csrf
                        @method('PATCH')

                        <p class="text-xs text-slate-600">
                            Promote another teammate to owner. You'll be demoted to admin — the workspace always has exactly one owner.
                        </p>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">New owner</label>
                            <select onchange="document.getElementById('transferForm').action = '/team/' + this.value + '/transfer-ownership'; document.getElementById('emailHint').textContent = this.options[this.selectedIndex].dataset.email || '';"
                                    class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                <option value="">— pick a teammate —</option>
                                @foreach ($users->where('id', '!=', auth()->id())->where(fn ($u) => ! $u->hasRole('owner')) as $candidate)
                                    <option value="{{ $candidate->id }}" data-email="{{ $candidate->email }}">{{ $candidate->name }} ({{ $candidate->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Confirm by typing their email: <span id="emailHint" class="font-mono text-slate-500"></span>
                            </label>
                            <input name="confirmation" autocomplete="off"
                                   class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        </div>

                        <button class="w-full px-3 py-2 rounded-md bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">
                            Transfer ownership
                        </button>
                    </form>
                </details>
            @endif
        </section>
    </div>

    @if ($pendingInvitations->isNotEmpty())
        <section class="mt-6 bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold">Pending invitations</h2>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-2">Email</th>
                        <th class="text-left px-5 py-2">Role</th>
                        <th class="text-left px-5 py-2">Invited by</th>
                        <th class="text-left px-5 py-2">Expires</th>
                        <th class="text-right px-5 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingInvitations as $invitation)
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3">{{ $invitation->email }}</td>
                            <td class="px-5 py-3"><span class="inline-block text-xs px-2 py-0.5 rounded bg-slate-100">{{ $invitation->role }}</span></td>
                            <td class="px-5 py-3 text-slate-600">{{ $invitation->invitedBy?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $invitation->expires_at->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    <button onclick="navigator.clipboard.writeText('{{ $invitation->acceptUrl() }}'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy link', 1500);" class="text-indigo-600 hover:underline">Copy link</button>
                                    @can('invite-users')
                                        <form method="POST" action="{{ route('team.revoke-invitation', $invitation) }}" onsubmit="return confirm('Revoke this invitation?')">
                                            @csrf @method('DELETE')
                                            <button class="text-rose-600 hover:underline">Revoke</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
