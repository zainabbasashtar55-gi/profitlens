@extends('layouts.tenant')

@section('title', 'Dashboard')

@php
    $k = $analytics['kpis'] ?? [];
    $fmt = fn ($n) => '$' . number_format((float) $n, 2);
    $chip = function ($pct) {
        if ($pct === null) return '<span class="text-xs text-slate-400">— new</span>';
        $cls = $pct >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700';
        $arrow = $pct >= 0 ? '↑' : '↓';
        return '<span class="text-xs px-1.5 py-0.5 rounded ' . $cls . '">' . $arrow . ' ' . abs($pct) . '%</span>';
    };
@endphp

@section('content')
    <div class="flex items-baseline justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-sm text-slate-600">Month-to-date · {{ \Carbon\Carbon::parse($analytics['period']['month_start'])->format('M j') }} – {{ \Carbon\Carbon::parse($analytics['period']['month_end'])->format('M j, Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('sales.create') }}" class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ New sale</a>
            <a href="{{ route('expenses.create') }}" class="px-3 py-2 rounded-md border border-slate-300 bg-white text-sm hover:bg-slate-50">+ New expense</a>
        </div>
    </div>

    {{-- KPI cards (data-* attrs let the Echo listener tick them up in real time) --}}
    <div class="grid md:grid-cols-4 gap-4 mb-6" id="kpi-grid"
         data-revenue="{{ $k['revenue']['current'] ?? 0 }}"
         data-profit="{{ $k['profit']['current'] ?? 0 }}"
         data-expenses="{{ $k['expenses']['current'] ?? 0 }}"
         data-net="{{ $k['net']['current'] ?? 0 }}">
        <div class="bg-white rounded-lg border border-slate-200 p-5 transition-all" data-kpi-card="revenue">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">MTD Revenue</div>
            <div class="mt-2 text-2xl font-bold" data-kpi="revenue">{{ $fmt($k['revenue']['current'] ?? 0) }}</div>
            <div class="mt-1">{!! $chip($k['revenue']['change_pct'] ?? null) !!} <span class="text-xs text-slate-500">vs last month</span></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5 transition-all" data-kpi-card="profit">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">MTD Profit</div>
            <div class="mt-2 text-2xl font-bold" data-kpi="profit">{{ $fmt($k['profit']['current'] ?? 0) }}</div>
            <div class="mt-1">{!! $chip($k['profit']['change_pct'] ?? null) !!} <span class="text-xs text-slate-500">margin {{ $k['margin_pct'] ?? 0 }}%</span></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5 transition-all" data-kpi-card="expenses">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">MTD Expenses</div>
            <div class="mt-2 text-2xl font-bold" data-kpi="expenses">{{ $fmt($k['expenses']['current'] ?? 0) }}</div>
            <div class="mt-1">{!! $chip($k['expenses']['change_pct'] ?? null) !!} <span class="text-xs text-slate-500">vs last month</span></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5 transition-all" data-kpi-card="net">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Net Profit</div>
            <div class="mt-2 text-2xl font-bold {{ ($k['net']['current'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}" data-kpi="net">{{ $fmt($k['net']['current'] ?? 0) }}</div>
            <div class="mt-1">{!! $chip($k['net']['change_pct'] ?? null) !!} <span class="text-xs text-slate-500">profit – expenses</span></div>
        </div>
    </div>

    {{-- Profit goal tracker (live: advances as paid sales come in) --}}
    <section class="bg-white rounded-lg border border-slate-200 p-5 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold flex items-center gap-2">
                Profit goal
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gradient-to-r from-indigo-500 to-fuchsia-500 text-white font-semibold">AI</span>
            </h2>
            <a href="{{ route('insights.index') }}" class="text-xs text-indigo-600 hover:underline">AI Insights →</a>
        </div>
        @include('tenant.insights._goal', ['goal' => $goal, 'goalProgress' => $goalProgress])
    </section>

    {{-- Chart + Expense breakdown --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-6">
        <section class="lg:col-span-2 bg-white rounded-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Profit trend (6 months)</h2>
            </div>
            <canvas id="profitChart" height="80"></canvas>
        </section>

        <section class="bg-white rounded-lg border border-slate-200 p-5">
            <h2 class="font-semibold mb-3">Expense breakdown</h2>
            @if (count($analytics['expense_breakdown']) === 0)
                <p class="text-sm text-slate-500">No expenses this month yet.</p>
            @else
                @php $totalExp = array_sum(array_column($analytics['expense_breakdown'], 'total')); @endphp
                <div class="space-y-2">
                    @foreach ($analytics['expense_breakdown'] as $row)
                        @php $pct = $totalExp > 0 ? ($row['total'] / $totalExp * 100) : 0; @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-700"><span class="inline-block w-2 h-2 rounded-full mr-1" style="background:{{ $row['color'] }}"></span>{{ $row['category'] }}</span>
                                <span class="text-slate-500">{{ $fmt($row['total']) }} · {{ number_format($pct, 1) }}%</span>
                            </div>
                            <div class="h-1.5 rounded bg-slate-100 overflow-hidden">
                                <div class="h-full rounded" style="width: {{ $pct }}%; background: {{ $row['color'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Top customers + Top products --}}
    <div class="grid lg:grid-cols-2 gap-4 mb-6">
        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold">Top customers</h2>
                <a class="text-xs text-indigo-600 hover:underline" href="{{ route('customers.index') }}">View all →</a>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-2">Customer</th>
                        <th class="text-right px-5 py-2">Revenue</th>
                        <th class="text-right px-5 py-2">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($analytics['top_customers'] as $c)
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-2">
                                <div class="font-medium">{{ $c['name'] }}</div>
                                @if ($c['company'])<div class="text-xs text-slate-500">{{ $c['company'] }}</div>@endif
                            </td>
                            <td class="px-5 py-2 text-right text-slate-700">{{ $fmt($c['revenue']) }}</td>
                            <td class="px-5 py-2 text-right {{ $c['profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $fmt($c['profit']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-6 text-center text-sm text-slate-500">No customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold">Top products</h2>
                <a class="text-xs text-indigo-600 hover:underline" href="{{ route('products.index') }}">View all →</a>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-2">Product</th>
                        <th class="text-right px-5 py-2">Sold</th>
                        <th class="text-right px-5 py-2">Revenue</th>
                        <th class="text-right px-5 py-2">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($analytics['top_products'] as $p)
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-2">{{ $p['name'] }}</td>
                            <td class="px-5 py-2 text-right text-slate-700">{{ $p['qty_sold'] }}</td>
                            <td class="px-5 py-2 text-right text-slate-700">{{ $fmt($p['revenue']) }}</td>
                            <td class="px-5 py-2 text-right {{ $p['profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $fmt($p['profit']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-sm text-slate-500">No sales yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    {{-- Team + recent activity --}}
    <div class="grid lg:grid-cols-3 gap-4">
        <section class="lg:col-span-2 bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold">Recent activity</h2>
                <a href="{{ route('activity.index') }}" class="text-xs text-indigo-600 hover:underline">Full audit log →</a>
            </header>
            <ul id="activity-feed" class="divide-y divide-slate-100 text-sm">
                @forelse ($analytics['recent_activity'] as $a)
                    @php
                        $iconCls = match ($a['event']) {
                            'created' => 'bg-emerald-100 text-emerald-700',
                            'updated' => 'bg-sky-100 text-sky-700',
                            'deleted' => 'bg-rose-100 text-rose-700',
                            default   => 'bg-slate-100 text-slate-700',
                        };
                        $icon = match ($a['event']) {
                            'created' => '+',
                            'updated' => '✎',
                            'deleted' => '✕',
                            default   => '●',
                        };
                    @endphp
                    <li class="px-5 py-3 flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 {{ $iconCls }} font-semibold text-xs">{{ $icon }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm">
                                <span class="font-medium">{{ $a['causer'] }}</span>
                                <span class="text-slate-600">{{ $a['description'] }}</span>
                            </div>
                            <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($a['at'])->diffForHumans() }}</div>
                        </div>
                    </li>
                @empty
                    <li id="activity-empty" class="px-5 py-8 text-center text-sm text-slate-500">No activity yet. Add a sale or expense to get started.</li>
                @endforelse
            </ul>
        </section>

        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold">Team</h2>
                <span class="text-xs text-slate-500">{{ $users->count() }}</span>
            </header>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($users as $u)
                    <li class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ $u->name }} @if ($u->id === auth()->id())<span class="text-xs text-slate-400">(you)</span>@endif</div>
                            <div class="text-xs text-slate-500">{{ $u->email }}</div>
                        </div>
                        <div>
                            @foreach ($u->roles as $role)
                                <span class="inline-block text-xs px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>

            @can('invite-users')
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Invite a teammate</div>
                    <form method="POST" action="{{ route('invitations.store') }}" class="space-y-2">
                        @csrf
                        <input name="email" type="email" required placeholder="teammate@email.com" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <div class="flex gap-2">
                            <select name="role" class="flex-1 rounded-md border border-slate-300 px-2 py-2 text-sm">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
                                Send invite
                            </button>
                        </div>
                    </form>
                </div>
            @endcan
        </section>
    </div>

    {{-- Pending invitations --}}
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
                        <th class="text-left px-5 py-2">Expires</th>
                        <th class="text-left px-5 py-2">Accept link</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingInvitations as $invitation)
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3">{{ $invitation->email }}</td>
                            <td class="px-5 py-3"><span class="inline-block text-xs px-2 py-0.5 rounded bg-slate-100">{{ $invitation->role }}</span></td>
                            <td class="px-5 py-3 text-slate-600">{{ $invitation->expires_at->diffForHumans() }}</td>
                            <td class="px-5 py-3"><a href="{{ $invitation->acceptUrl() }}" class="text-xs text-indigo-600 hover:underline break-all">{{ \Str::limit($invitation->acceptUrl(), 50) }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <script>
        const trend = @json($analytics['profit_trend']);
        const profitChart = new Chart(document.getElementById('profitChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: trend.map(t => t.label),
                datasets: [
                    { label: 'Revenue',  data: trend.map(t => t.revenue),  borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', tension: 0.3, fill: true },
                    { label: 'Profit',   data: trend.map(t => t.profit),   borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.05)', tension: 0.3 },
                    { label: 'Expenses', data: trend.map(t => t.expenses), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.05)', tension: 0.3 },
                    { label: 'Net',      data: trend.map(t => t.net),      borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.05)', tension: 0.3, borderDash: [4, 4] },
                ],
            },
            options: {
                responsive: true,
                animation: { duration: 600 },
                plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } },
                scales: { y: { ticks: { callback: v => '$' + v.toLocaleString() } } },
            },
        });
        window.profitChart = profitChart;

        // ---------------- Live updates from Echo (WebSocket) ----------------
        const grid = document.getElementById('kpi-grid');
        const fmtMoney = (n) => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fmtMoneyCompact = (n) => {
            const abs = Math.abs(n);
            return abs >= 1000 && abs % 1 === 0
                ? '$' + Math.round(n).toLocaleString('en-US')
                : fmtMoney(n);
        };

        function bumpKpi(key, delta, signal = 'pulse') {
            const card = document.querySelector(`[data-kpi-card="${key}"]`);
            const valueEl = document.querySelector(`[data-kpi="${key}"]`);
            if (!grid || !card || !valueEl) return;
            const current = Math.round((parseFloat(grid.dataset[key] || '0') + delta) * 100) / 100;
            grid.dataset[key] = current;
            valueEl.textContent = fmtMoney(current);
            // Visual signal: ring flash + lift
            const ringColor = signal === 'up' ? 'ring-emerald-400' : 'ring-rose-400';
            card.classList.add('ring-2', ringColor, 'scale-[1.02]', 'shadow-md');
            setTimeout(() => card.classList.remove('ring-2', ringColor, 'scale-[1.02]', 'shadow-md'), 1500);
        }

        // Bump the current (last) month's data points and ask Chart.js to redraw.
        function bumpChart({ revenueDelta = 0, profitDelta = 0, expenseDelta = 0 }) {
            if (!window.profitChart) return;
            const ds = window.profitChart.data.datasets;
            const last = ds[0].data.length - 1;
            if (last < 0) return;
            ds[0].data[last] = (ds[0].data[last] || 0) + revenueDelta;
            ds[1].data[last] = (ds[1].data[last] || 0) + profitDelta;
            ds[2].data[last] = (ds[2].data[last] || 0) + expenseDelta;
            ds[3].data[last] = (ds[1].data[last] || 0) - (ds[2].data[last] || 0); // net = profit - expenses
            window.profitChart.update();
        }

        function prependActivity(event, description, causer) {
            const feed = document.getElementById('activity-feed');
            if (!feed) return;
            const empty = document.getElementById('activity-empty');
            if (empty) empty.remove();

            const iconCls = event === 'sale' ? 'bg-emerald-100 text-emerald-700'
                        : event === 'expense' ? 'bg-rose-100 text-rose-700'
                        : 'bg-indigo-100 text-indigo-700';
            const icon = event === 'sale' ? '+' : event === 'expense' ? '✕' : '👋';
            const li = document.createElement('li');
            li.className = 'px-5 py-3 flex items-center gap-3 bg-amber-50 transition-colors duration-1000';
            li.innerHTML = `
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 ${iconCls} font-semibold text-xs">${icon}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm">
                        <span class="font-medium">${causer}</span>
                        <span class="text-slate-600">${description}</span>
                    </div>
                    <div class="text-xs text-slate-500">just now</div>
                </div>
            `;
            feed.prepend(li);
            // Trim to 8 most recent so the feed doesn't grow unbounded
            while (feed.children.length > 8) feed.lastElementChild.remove();
            // Fade the new-row highlight after 3s
            setTimeout(() => li.classList.remove('bg-amber-50'), 3000);
        }

        window.addEventListener('profitlens:sale-recorded', (e) => {
            const d = e.detail;
            // Only update for *paid* sales — drafts/pending don't change KPIs (matches server-side filter).
            if (d.status && d.status !== 'paid') return;
            bumpKpi('revenue', d.revenue, 'up');
            bumpKpi('profit',  d.profit,  'up');
            bumpKpi('net',     d.profit,  'up');
            bumpChart({ revenueDelta: d.revenue, profitDelta: d.profit });
            prependActivity('sale', `recorded sale #${d.id} — ${d.customer} ${fmtMoneyCompact(d.revenue)}`, d.actor);
        });

        window.addEventListener('profitlens:expense-logged', (e) => {
            const d = e.detail;
            bumpKpi('expenses', d.amount, 'down');
            bumpKpi('net',     -d.amount, 'down');
            bumpChart({ expenseDelta: d.amount });
            prependActivity('expense', `logged expense ${fmtMoneyCompact(d.amount)} — ${d.description}`, d.actor);
        });
    </script>
@endsection
