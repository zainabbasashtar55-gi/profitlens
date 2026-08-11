<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', tenant('name')); ?> · ProfitLens</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://js.pusher.com/8.4/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <style>html, body { font-family: 'Inter', system-ui, sans-serif; }</style>
    <?php echo $__env->make('layouts._profitlens-theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        /* Matte workspace chrome, balanced by the light product canvas. */
        body > .flex > aside {
            width: 16rem;
            background: var(--pl-matte) !important;
            border-color: #272B30 !important;
            color: #F8FAFC;
            box-shadow: 14px 0 38px rgba(15, 23, 42, .08);
        }

        body > .flex > aside > div:first-child,
        body > .flex > aside > div:last-child {
            border-color: #2A2E33 !important;
        }

        body > .flex > aside > div:first-child .inline-block {
            border-radius: 999px;
            background: linear-gradient(145deg, #60A5FA, var(--pl-primary)) !important;
            box-shadow: 0 6px 18px rgba(37, 99, 235, .38);
        }

        body > .flex > aside .text-slate-500,
        body > .flex > aside .text-slate-400 {
            color: #8F9AA5 !important;
        }

        body > .flex > aside nav > a {
            color: #C8D0D8 !important;
            border: 1px solid transparent;
            border-radius: .625rem;
        }

        body > .flex > aside nav > a:hover {
            color: #FFFFFF !important;
            background: var(--pl-matte-soft) !important;
            border-color: #2B3036;
        }

        body > .flex > aside nav > a.bg-indigo-50 {
            color: #FFFFFF !important;
            background: var(--pl-primary) !important;
            border-color: #3B82F6;
            box-shadow: 0 7px 20px rgba(37, 99, 235, .22);
        }

        body > .flex > aside nav > a.bg-indigo-50 svg { color: #FFFFFF; }
        body > .flex > aside nav .bg-gradient-to-r {
            background: #262B30 !important;
            color: #93C5FD !important;
            border: 1px solid #3B4652;
        }

        body > .flex > aside > div:last-child .font-medium { color: #F8FAFC; }
        body > .flex > aside > div:last-child button { color: #94A3B8 !important; }
        body > .flex > aside > div:last-child button:hover { color: #FCA5A5 !important; }

        body > .flex > .flex-1 {
            min-width: 0;
            background:
                radial-gradient(circle at 96% 0%, rgba(37, 99, 235, .045), transparent 24rem),
                var(--pl-surface);
        }
    </style>
    <?php if(auth()->guard()->check()): ?>
        <script>
            window.profitlens = {
                tenantId: <?php echo json_encode(tenant('id'), 15, 512) ?>,
                tenantName: <?php echo json_encode(tenant('name'), 15, 512) ?>,
                userId: <?php echo json_encode(auth()->id(), 15, 512) ?>,
                userName: <?php echo json_encode(auth()->user()->name, 15, 512) ?>,
                reverb: {
                    key: <?php echo json_encode(env('REVERB_APP_KEY'), 15, 512) ?>,
                    host: <?php echo json_encode(env('REVERB_HOST', '127.0.0.1'), 512) ?>,
                    port: <?php echo json_encode((int) env('REVERB_PORT', 8080), 512) ?>,
                    scheme: <?php echo json_encode(env('REVERB_SCHEME', 'http'), 512) ?>,
                },
            };
        </script>
    <?php endif; ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">

<?php if(auth()->guard()->check()): ?>
    <div class="flex min-h-screen">
        <aside class="w-60 bg-white border-r border-slate-200 flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-7 h-7 rounded bg-indigo-600"></span>
                    <div class="leading-tight">
                        <div class="font-semibold text-sm"><?php echo e(tenant('name')); ?></div>
                        <div class="text-xs text-slate-500"><?php echo e(ucfirst(tenant('plan') ?? 'free')); ?></div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-3 text-sm space-y-1">
                <?php $route = request()->route()?->getName(); ?>
                <a href="<?php echo e(route('tenant.dashboard')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e($route === 'tenant.dashboard' ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="<?php echo e(route('sales.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'sales.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Sales
                </a>
                <a href="<?php echo e(route('invoices.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'invoices.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Invoices
                </a>
                <a href="<?php echo e(route('customers.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'customers.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Customers
                </a>
                <a href="<?php echo e(route('products.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'products.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v10l-8 4m8-14L12 11m0 0L4 7m8 4v10"/></svg>
                    Products
                </a>
                <a href="<?php echo e(route('expenses.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'expenses.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Expenses
                </a>

                <a href="<?php echo e(route('insights.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'insights.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    AI Insights
                    <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-gradient-to-r from-indigo-500 to-fuchsia-500 text-white font-semibold">AI</span>
                </a>

                <div class="pt-4 pb-1 px-3 text-xs uppercase font-semibold text-slate-400">Workspace</div>
                <a href="<?php echo e(route('team.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'team.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Team
                </a>
                <a href="<?php echo e(route('activity.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'activity.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Activity log
                </a>
                <?php if(auth()->user()->hasRole('owner')): ?>
                    <a href="<?php echo e(route('billing.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'billing.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Billing &amp; plan
                    </a>
                    <a href="<?php echo e(route('settings.index')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e(str_starts_with($route ?? '', 'settings.') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Settings
                    </a>
                <?php endif; ?>

                <div class="pt-4 pb-1 px-3 text-xs uppercase font-semibold text-slate-400">Reports</div>
                <a href="<?php echo e(route('reports.profit-loss')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md <?php echo e($route === 'reports.profit-loss' ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 4h6a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Profit &amp; Loss
                </a>
                <a href="<?php echo e(route('reports.sales.csv')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md text-slate-700 hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V3"/></svg>
                    Export sales CSV
                </a>
                <a href="<?php echo e(route('reports.expenses.csv')); ?>" class="flex items-center gap-2 px-3 py-2 rounded-md text-slate-700 hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V3"/></svg>
                    Export expenses CSV
                </a>
            </nav>

            <div class="p-3 border-t border-slate-200 text-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium"><?php echo e(auth()->user()->name); ?></div>
                        <div class="text-xs text-slate-500"><?php echo e(auth()->user()->roles->pluck('name')->join(', ') ?: 'no role'); ?></div>
                    </div>
                    <form action="<?php echo e(route('tenant.logout')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button class="text-xs text-slate-500 hover:text-rose-600">Log out</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col">
            
            <div class="px-8 pt-4 flex items-center justify-end gap-3">
                <div id="cashflow-banner" class="hidden flex-1 rounded-md bg-rose-50 border border-rose-200 px-4 py-2.5 text-rose-800 text-sm"></div>
                <div id="presence-cluster" class="flex items-center -space-x-2">
                    
                </div>
            </div>

            <?php if(session('status')): ?>
                <div class="px-8 pt-3">
                    <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-emerald-800 text-sm">
                        <?php echo e(session('status')); ?>

                    </div>
                </div>
            <?php endif; ?>

            <main class="flex-1 px-8 py-6">
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2 w-80 pointer-events-none"></div>

    
    <div id="ocr-tray" class="fixed bottom-4 right-4 z-50 w-80 space-y-2 pointer-events-none"></div>

    <script>
        // ---------------- Toast helper ----------------
        function showToast({ title, body, color = 'indigo', emoji = '' }) {
            const colors = {
                indigo:  'bg-indigo-50 border-indigo-200 text-indigo-800',
                emerald: 'bg-emerald-50 border-emerald-200 text-emerald-800',
                rose:    'bg-rose-50 border-rose-200 text-rose-800',
                amber:   'bg-amber-50 border-amber-200 text-amber-800',
                sky:     'bg-sky-50 border-sky-200 text-sky-800',
            };
            const el = document.createElement('div');
            el.className = `pointer-events-auto rounded-lg border shadow-lg px-4 py-3 ${colors[color] || colors.indigo} transform transition-all duration-300 translate-x-full opacity-0`;
            el.innerHTML = `
                <div class="flex items-start gap-2">
                    <div class="text-xl">${emoji}</div>
                    <div class="flex-1">
                        <div class="font-semibold text-sm">${title}</div>
                        ${body ? `<div class="text-xs mt-0.5 opacity-80">${body}</div>` : ''}
                    </div>
                    <button onclick="this.closest('.pointer-events-auto').remove()" class="text-current opacity-50 hover:opacity-100 text-sm leading-none">×</button>
                </div>
            `;
            document.getElementById('toast-container').appendChild(el);
            requestAnimationFrame(() => {
                el.classList.remove('translate-x-full', 'opacity-0');
            });
            setTimeout(() => {
                el.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => el.remove(), 300);
            }, 6000);
        }

        // ---------------- Echo / Reverb bootstrap ----------------
        const cfg = window.profitlens;
        if (cfg && cfg.reverb && cfg.reverb.key) {
            window.Pusher = Pusher;
            window.Echo = new Echo.default({
                broadcaster: 'reverb',
                key:        cfg.reverb.key,
                wsHost:     cfg.reverb.host,
                wsPort:     cfg.reverb.port,
                wssPort:    cfg.reverb.port,
                forceTLS:   cfg.reverb.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                },
            });

            const fmtAmount = (n) => {
                const abs = Math.abs(n);
                return abs >= 1000 && abs % 1 === 0
                    ? '$' + Math.round(n).toLocaleString('en-US')
                    : '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            // ── Activity channel: sales + expenses by teammates ──
            Echo.private(`tenant.${cfg.tenantId}.activity`)
                .listen('.sale.recorded', (e) => {
                    // Always re-dispatch so the dashboard updates KPIs/chart for everyone (including the actor).
                    window.dispatchEvent(new CustomEvent('profitlens:sale-recorded', { detail: e }));
                    if (e.actor_id === cfg.userId) return; // don't toast the actor
                    showToast({
                        emoji: '💰',
                        color: 'emerald',
                        title: `${e.actor} just recorded a sale`,
                        body:  `${e.customer} ${fmtAmount(e.revenue)}`,
                    });
                })
                .listen('.expense.logged', (e) => {
                    window.dispatchEvent(new CustomEvent('profitlens:expense-logged', { detail: e }));
                    if (e.actor_id === cfg.userId) return;
                    showToast({
                        emoji: '🧾',
                        color: 'rose',
                        title: `${e.actor} logged an expense`,
                        body:  `${e.description} — ${fmtAmount(e.amount)}${e.category ? ' · ' + e.category : ''}`,
                    });
                })
                .listen('.goal.progress', (e) => updateGoalUi(e));

            // Live profit-goal progress — updates any rendered goal card in place.
            function updateGoalUi(p) {
                document.querySelectorAll('[data-goal-wrap]').forEach((wrap) => {
                    const pct = Math.min(100, Number(p.pct) || 0);
                    const cur   = wrap.querySelector('[data-goal-current]');
                    const bar   = wrap.querySelector('[data-goal-bar]');
                    const pctEl = wrap.querySelector('[data-goal-pct]');
                    const rem   = wrap.querySelector('[data-goal-remaining]');
                    const chip  = wrap.querySelector('[data-goal-chip]');
                    if (cur)   cur.textContent = fmtAmount(p.current);
                    if (bar) {
                        bar.style.width = pct + '%';
                        bar.classList.toggle('bg-emerald-500', !!p.on_track);
                        bar.classList.toggle('bg-amber-500', !p.on_track);
                    }
                    if (pctEl) pctEl.textContent = p.pct + '%';
                    if (rem)   rem.textContent = fmtAmount(p.remaining) + ' to go';
                    if (chip) {
                        chip.textContent = p.on_track ? 'On track' : 'Behind pace';
                        chip.className = 'inline-block px-1.5 py-0.5 rounded ' + (p.on_track ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700');
                    }
                    // Briefly flash to signal the live update.
                    if (bar) {
                        bar.classList.add('ring-2', 'ring-offset-1', 'ring-indigo-300');
                        setTimeout(() => bar.classList.remove('ring-2', 'ring-offset-1', 'ring-indigo-300'), 1200);
                    }
                });
            }

            // ── Team channel: invitations accepted ──
            Echo.private(`tenant.${cfg.tenantId}.team`)
                .listen('.invitation.accepted', (e) => {
                    showToast({
                        emoji: '👋',
                        color: 'indigo',
                        title: `${e.name} joined the workspace`,
                        body:  `as a ${e.role}`,
                    });
                    window.dispatchEvent(new CustomEvent('profitlens:invitation-accepted', { detail: e }));
                });

            // ── Alerts channel: low cash warnings ──
            Echo.private(`tenant.${cfg.tenantId}.alerts`)
                .listen('.cashflow.warning', (e) => {
                    const banner = document.getElementById('cashflow-banner');
                    if (banner) {
                        banner.textContent = e.message;
                        banner.classList.remove('hidden');
                    }
                    showToast({
                        emoji: '⚠️',
                        color: 'rose',
                        title: 'Low cashflow alert',
                        body:  e.message,
                    });
                });

            // ── Personal channel: big-sale celebrations + OCR progress ──
            const ocrTray = document.getElementById('ocr-tray');
            const ocrCards = new Map(); // job_id → element
            function renderOcrCard(payload) {
                let card = ocrCards.get(payload.job_id);
                if (!card) {
                    card = document.createElement('div');
                    card.dataset.jobId = payload.job_id;
                    card.className = 'pointer-events-auto rounded-lg border bg-white shadow-lg p-3 transform transition-all duration-300 translate-y-2 opacity-0';
                    ocrTray.appendChild(card);
                    ocrCards.set(payload.job_id, card);
                    requestAnimationFrame(() => card.classList.remove('translate-y-2', 'opacity-0'));
                }

                const isDone   = payload.status === 'done';
                const isFailed = payload.status === 'failed';
                const barColor = isFailed ? 'bg-rose-500' : isDone ? 'bg-emerald-500' : 'bg-indigo-500';
                const dot      = isFailed ? '🚫' : isDone ? '✅' : '📄';

                card.innerHTML = `
                    <div class="flex items-start gap-2">
                        <div class="text-lg">${dot}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-slate-700">
                                ${isDone ? 'Receipt processed' : isFailed ? 'OCR failed' : 'OCR processing…'}
                            </div>
                            <div class="text-xs text-slate-500 truncate">${payload.message ?? ''}</div>
                            <div class="mt-2 h-1.5 rounded bg-slate-100 overflow-hidden">
                                <div class="h-full ${barColor} transition-all duration-500" style="width: ${payload.percent}%"></div>
                            </div>
                            <div class="mt-1 text-[10px] text-slate-400 tabular-nums">${payload.percent}%</div>
                        </div>
                        <button class="text-slate-300 hover:text-slate-600 text-sm leading-none" onclick="this.closest('[data-job-id]').remove()">×</button>
                    </div>
                `;

                if (isDone || isFailed) {
                    setTimeout(() => {
                        card.classList.add('translate-y-2', 'opacity-0');
                        setTimeout(() => { card.remove(); ocrCards.delete(payload.job_id); }, 300);
                    }, 4000);
                }
            }

            Echo.private(`user.${cfg.userId}`)
                .listen('.sale.big', (e) => {
                    showToast({
                        emoji: '🎉',
                        color: 'amber',
                        title: 'Biggest sale of the month!',
                        body:  e.message,
                    });
                })
                .listen('.ocr.progress', (e) => {
                    renderOcrCard(e);
                    window.dispatchEvent(new CustomEvent('profitlens:ocr-progress', { detail: e }));
                });

            // ── Presence channel: who's online ──
            Echo.join(`tenant.${cfg.tenantId}`)
                .here((users) => renderPresence(users))
                .joining((u) => {
                    renderPresence(null, u, 'joining');
                    showToast({ emoji: '🟢', color: 'sky', title: `${u.name} is online` });
                })
                .leaving((u) => renderPresence(null, u, 'leaving'));

            const presenceState = new Map();
            function renderPresence(initialList, single = null, action = null) {
                if (initialList) {
                    presenceState.clear();
                    initialList.forEach(u => presenceState.set(u.id, u));
                } else if (single && action === 'joining') {
                    presenceState.set(single.id, single);
                } else if (single && action === 'leaving') {
                    presenceState.delete(single.id);
                }

                const cluster = document.getElementById('presence-cluster');
                if (!cluster) return;
                cluster.innerHTML = '';
                const list = Array.from(presenceState.values()).slice(0, 5);
                list.forEach(u => {
                    const initials = u.name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
                    const isYou = u.id === cfg.userId;
                    const dot = document.createElement('div');
                    dot.className = 'w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-xs font-semibold bg-indigo-100 text-indigo-700';
                    dot.title = u.name + (isYou ? ' (you)' : '') + ' · ' + u.role;
                    dot.textContent = initials;
                    cluster.appendChild(dot);
                });
                if (presenceState.size > 5) {
                    const more = document.createElement('div');
                    more.className = 'w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-xs font-medium bg-slate-100 text-slate-600';
                    more.textContent = '+' + (presenceState.size - 5);
                    cluster.appendChild(more);
                }
                if (presenceState.size > 0) {
                    const label = document.createElement('div');
                    label.className = 'ml-3 text-xs text-slate-500';
                    label.textContent = presenceState.size === 1
                        ? 'just you online'
                        : `${presenceState.size} online`;
                    cluster.appendChild(label);
                }
            }
        }
    </script>
<?php else: ?>
    <?php if(session('status')): ?>
        <div class="max-w-6xl mx-auto px-6 pt-6">
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                <?php echo e(session('status')); ?>

            </div>
        </div>
    <?php endif; ?>
    <main class="max-w-6xl mx-auto px-6 py-10">
        <?php echo $__env->yieldContent('content'); ?>
    </main>
<?php endif; ?>

</body>
</html>
<?php /**PATH D:\profitlens\resources\views/layouts/tenant.blade.php ENDPATH**/ ?>