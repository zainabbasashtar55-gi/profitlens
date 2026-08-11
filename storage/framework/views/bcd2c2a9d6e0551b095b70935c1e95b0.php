<?php $__env->startSection('title', 'AI Insights'); ?>

<?php
    $fmt  = fn ($n) => '$' . number_format((float) $n, 2);
    $fmt0 = fn ($n) => '$' . number_format((float) $n, 0);
?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-baseline justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                AI Insights
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gradient-to-r from-indigo-500 to-fuchsia-500 text-white font-semibold align-middle">AI</span>
            </h1>
            <p class="text-sm text-slate-600">Forecasts, anomaly detection, and a chat that knows your numbers.</p>
        </div>
    </div>

    <?php if (! ($aiEnabled)): ?>
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span class="font-semibold">Running in offline mode.</span>
            Forecasts &amp; anomaly detection work without a key. Add <code class="px-1 rounded bg-amber-100">ANTHROPIC_API_KEY</code> to your <code class="px-1 rounded bg-amber-100">.env</code> to unlock the free-form AI chat and richer narratives.
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-4">
        
        <section class="lg:col-span-2 bg-white rounded-lg border border-slate-200 flex flex-col" style="min-height: 520px">
            <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold">Ask your numbers</h2>
                <span class="text-xs text-slate-400">e.g. “Why did profit drop last month?”</span>
            </header>

            <div id="chat-log" class="flex-1 overflow-y-auto p-5 space-y-4 text-sm">
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-fuchsia-500 text-white flex items-center justify-center text-xs shrink-0">AI</div>
                    <div class="bg-slate-50 rounded-lg px-3 py-2 text-slate-700">
                        Hi! Ask me anything about your finances — profit trends, where money is going, cash flow, customers. I read your live data each time.
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 p-3">
                <div class="flex flex-wrap gap-1.5 mb-2" id="chat-suggestions">
                    <?php $__currentLoopData = [
                        'Why did profit change vs last month?',
                        'Where is most of my money going?',
                        'How is my cash flow looking?',
                        'Which customers drive the most profit?',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button" class="suggestion text-xs px-2 py-1 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50"><?php echo e($s); ?></button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <form id="chat-form" class="flex gap-2">
                    <input id="chat-input" type="text" autocomplete="off" maxlength="1000"
                           placeholder="Ask about your finances…"
                           class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <button id="chat-send" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">Send</button>
                </form>
            </div>
        </section>

        
        <div class="space-y-4">
            
            <section class="bg-white rounded-lg border border-slate-200 p-5">
                <h2 class="font-semibold mb-3">Profit goal</h2>
                <?php echo $__env->make('tenant.insights._goal', ['goal' => $goal, 'goalProgress' => $goalProgress], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </section>

            
            <section class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold">Anomaly detection</h2>
                    <span class="text-xs text-slate-400">vs <?php echo e($anomalies['baseline_months']); ?>-mo avg</span>
                </div>
                <?php if($anomalies['summary']): ?>
                    <p class="text-sm text-slate-600 mb-3"><?php echo e($anomalies['summary']); ?></p>
                <?php endif; ?>
                <?php if(count($anomalies['anomalies']) === 0): ?>
                    <div class="text-sm text-emerald-700 bg-emerald-50 rounded-md px-3 py-2">✓ Nothing unusual this month.</div>
                <?php else: ?>
                    <ul class="space-y-2">
                        <?php $__currentLoopData = $anomalies['anomalies']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="flex items-start gap-2 text-sm">
                                <span class="mt-0.5 inline-block w-2 h-2 rounded-full shrink-0 <?php echo e($a['severity'] === 'high' ? 'bg-rose-500' : 'bg-amber-500'); ?>"></span>
                                <span class="text-slate-700"><?php echo e($a['message']); ?></span>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>

    
    <section class="mt-4 bg-white rounded-lg border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="font-semibold">Cash flow forecast · next <?php echo e($forecast['horizon_days']); ?> days</h2>
                <p class="text-xs text-slate-500"><?php echo e($forecast['note']); ?></p>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-500 uppercase tracking-wide">Projected net</div>
                <div class="text-xl font-bold <?php echo e($forecast['projected_net_90d'] >= 0 ? 'text-emerald-600' : 'text-rose-600'); ?>"><?php echo e($fmt($forecast['projected_net_90d'])); ?></div>
            </div>
        </div>

        <?php if(! $forecast['has_data']): ?>
            <p class="text-sm text-slate-500 py-6 text-center">Not enough history yet — record some sales and expenses to see a projection.</p>
        <?php else: ?>
            <div class="grid sm:grid-cols-4 gap-3 mb-4 text-sm">
                <div class="rounded-md bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Avg daily inflow</div>
                    <div class="font-semibold text-emerald-600"><?php echo e($fmt($forecast['daily']['revenue'])); ?></div>
                </div>
                <div class="rounded-md bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Avg daily outflow</div>
                    <div class="font-semibold text-rose-600"><?php echo e($fmt($forecast['daily']['outflow'])); ?></div>
                </div>
                <div class="rounded-md bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Recurring / mo</div>
                    <div class="font-semibold text-slate-700"><?php echo e($fmt($forecast['recurring_monthly'])); ?></div>
                </div>
                <div class="rounded-md bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Avg daily net</div>
                    <div class="font-semibold <?php echo e($forecast['daily']['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600'); ?>"><?php echo e($fmt($forecast['daily']['net'])); ?></div>
                </div>
            </div>
            <canvas id="forecastChart" height="90"></canvas>
        <?php endif; ?>
    </section>

    <script>
        // ───────────────── Cash-flow forecast chart ─────────────────
        const forecast = <?php echo json_encode($forecast, 15, 512) ?>;
        if (forecast.has_data && document.getElementById('forecastChart')) {
            new Chart(document.getElementById('forecastChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: forecast.weeks.map(w => `${w.label} (${w.date})`),
                    datasets: [
                        { type: 'line', label: 'Cumulative net', data: forecast.weeks.map(w => w.cumulative), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', tension: 0.3, fill: true, yAxisID: 'y' },
                        { type: 'bar', label: 'Inflow', data: forecast.weeks.map(w => w.inflow), backgroundColor: 'rgba(16,185,129,0.5)', yAxisID: 'y' },
                        { type: 'bar', label: 'Outflow', data: forecast.weeks.map(w => -w.outflow), backgroundColor: 'rgba(239,68,68,0.5)', yAxisID: 'y' },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: { y: { ticks: { callback: v => '$' + Number(v).toLocaleString() } } },
                },
            });
        }

        // ───────────────── AI chat ─────────────────
        (function () {
            const form  = document.getElementById('chat-form');
            const input = document.getElementById('chat-input');
            const send  = document.getElementById('chat-send');
            const log   = document.getElementById('chat-log');
            const csrf  = document.querySelector('meta[name="csrf-token"]').content;
            const history = []; // {role, content}

            function bubble(role, text, opts = {}) {
                const wrap = document.createElement('div');
                wrap.className = 'flex gap-3' + (role === 'user' ? ' flex-row-reverse' : '');
                const avatar = role === 'user'
                    ? '<div class="w-7 h-7 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs shrink-0">You</div>'
                    : '<div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-fuchsia-500 text-white flex items-center justify-center text-xs shrink-0">AI</div>';
                const bubbleCls = role === 'user'
                    ? 'bg-indigo-600 text-white'
                    : (opts.fallback ? 'bg-amber-50 text-amber-900 border border-amber-200' : 'bg-slate-50 text-slate-700');
                const body = document.createElement('div');
                body.className = `rounded-lg px-3 py-2 max-w-[85%] whitespace-pre-wrap ${bubbleCls}`;
                body.textContent = text;
                wrap.innerHTML = avatar;
                wrap.appendChild(body);
                log.appendChild(wrap);
                log.scrollTop = log.scrollHeight;
                return body;
            }

            async function ask(question) {
                if (!question.trim()) return;
                input.value = '';
                send.disabled = true;
                bubble('user', question);
                history.push({ role: 'user', content: question });

                const thinking = bubble('assistant', '…');
                thinking.classList.add('animate-pulse');

                try {
                    const res = await fetch(<?php echo json_encode(route('insights.chat'), 15, 512) ?>, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ message: question, history: history.slice(0, -1).slice(-10) }),
                    });
                    const data = await res.json();
                    thinking.remove();
                    if (!res.ok) {
                        bubble('assistant', data.message || 'Something went wrong. Please try again.', { fallback: true });
                    } else {
                        bubble('assistant', data.answer, { fallback: data.fallback });
                        history.push({ role: 'assistant', content: data.answer });
                    }
                } catch (e) {
                    thinking.remove();
                    bubble('assistant', 'Network error — please try again.', { fallback: true });
                } finally {
                    send.disabled = false;
                    input.focus();
                }
            }

            form.addEventListener('submit', (e) => { e.preventDefault(); ask(input.value); });
            document.querySelectorAll('.suggestion').forEach(btn => {
                btn.addEventListener('click', () => ask(btn.textContent));
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/insights/index.blade.php ENDPATH**/ ?>