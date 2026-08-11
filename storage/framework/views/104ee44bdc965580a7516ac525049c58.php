<?php $__env->startSection('title', 'Pricing'); ?>

<?php
    $plans = config('plans.plans');
    $stripeReady = (bool) config('cashier.key');
    $fmt = fn ($cents) => $cents === 0 ? '$0' : '$' . number_format($cents / 100, 0);
?>

<?php $__env->startSection('content'); ?>
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold tracking-tight">Simple, workspace-based pricing</h1>
        <p class="mt-3 text-slate-600 max-w-2xl mx-auto">
            Every plan comes with its own isolated database, role-based access, and the full ProfitLens dashboard.
            One subscription covers your entire team.
        </p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slug => $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $highlight = $plan['highlight'] ?? false; ?>

            <div class="bg-white rounded-lg p-6 border-2 <?php echo e($highlight ? 'border-indigo-500 shadow-lg' : 'border-slate-200'); ?> relative flex flex-col">
                <?php if($highlight): ?>
                    <span class="absolute -top-3 right-4 px-2 py-0.5 text-xs font-medium bg-indigo-600 text-white rounded">Most popular</span>
                <?php endif; ?>

                <div>
                    <div class="text-sm font-semibold uppercase tracking-wide <?php echo e($highlight ? 'text-indigo-600' : 'text-slate-500'); ?>">
                        <?php echo e($plan['name']); ?>

                    </div>

                    <div class="mt-2 flex items-baseline gap-1">
                        <?php if($slug === 'enterprise'): ?>
                            <span class="text-4xl font-bold">Custom</span>
                        <?php else: ?>
                            <span class="text-4xl font-bold"><?php echo e($fmt($plan['price_cents'])); ?></span>
                            <span class="text-sm text-slate-500">/mo</span>
                        <?php endif; ?>
                    </div>

                    <p class="mt-3 text-sm text-slate-600">
                        <?php switch($slug):
                            case ('free'): ?> Get started for free. Perfect for solo founders. <?php break; ?>
                            <?php case ('pro'): ?> For growing teams that need real headroom. <?php break; ?>
                            <?php case ('enterprise'): ?> For organizations that need scale + compliance. <?php break; ?>
                        <?php endswitch; ?>
                    </p>

                    <ul class="mt-6 space-y-2 text-sm text-slate-700">
                        <?php $__currentLoopData = $plan['features']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <?php echo e($f); ?>

                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    
                    <div class="mt-5 pt-5 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                        <div class="font-semibold text-slate-600 uppercase tracking-wide mb-1">Limits</div>
                        <div class="flex justify-between"><span>Team members</span><span class="font-mono"><?php echo e($plan['limits']['users'] === PHP_INT_MAX ? '∞' : $plan['limits']['users']); ?></span></div>
                        <div class="flex justify-between"><span>Sales / month</span><span class="font-mono"><?php echo e($plan['limits']['sales_per_month'] === PHP_INT_MAX ? '∞' : number_format($plan['limits']['sales_per_month'])); ?></span></div>
                        <div class="flex justify-between"><span>Products</span><span class="font-mono"><?php echo e($plan['limits']['products'] === PHP_INT_MAX ? '∞' : number_format($plan['limits']['products'])); ?></span></div>
                        <div class="flex justify-between"><span>Receipt storage</span><span class="font-mono"><?php echo e($plan['limits']['storage_mb'] >= 51200 ? '50 GB' : ($plan['limits']['storage_mb'] >= 1024 ? round($plan['limits']['storage_mb']/1024) . ' GB' : $plan['limits']['storage_mb'] . ' MB')); ?></span></div>
                    </div>
                </div>

                <div class="mt-6 pt-6">
                    <?php if($slug === 'enterprise'): ?>
                        <a href="mailto:sales@profitlens.test?subject=Enterprise%20plan%20enquiry"
                           class="block w-full text-center px-4 py-2.5 rounded-md border border-slate-300 hover:bg-slate-50 text-sm font-medium">
                            Contact sales
                        </a>
                    <?php elseif($slug === 'free'): ?>
                        <a href="<?php echo e(route('signup')); ?>?plan=free"
                           class="block w-full text-center px-4 py-2.5 rounded-md border border-slate-300 hover:bg-slate-50 text-sm font-medium">
                            Start free
                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(route('signup')); ?>?plan=<?php echo e($slug); ?>"
                           class="block w-full text-center px-4 py-2.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium">
                            Start <?php echo e($plan['name']); ?> trial
                        </a>
                    <?php endif; ?>

                    <p class="mt-3 text-xs text-center text-slate-500">
                        Already have a workspace? <a href="#" onclick="event.preventDefault(); document.getElementById('login-helper').classList.toggle('hidden')" class="text-indigo-600 hover:underline">Sign in</a>
                    </p>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div id="login-helper" class="hidden mt-6 max-w-md mx-auto bg-white rounded-lg border border-slate-200 p-5">
        <h3 class="font-semibold mb-2">Sign in to your workspace</h3>
        <p class="text-sm text-slate-600 mb-3">Enter your workspace subdomain — we'll send you to its login page.</p>
        <form onsubmit="event.preventDefault(); window.location='http://'+document.getElementById('sub').value+'.<?php echo e(config('app.tenant_domain')); ?>:'+window.location.port+'/login'">
            <div class="flex rounded-md border border-slate-300 overflow-hidden">
                <input id="sub" type="text" required placeholder="acme" class="flex-1 px-3 py-2 focus:outline-none">
                <span class="px-3 py-2 bg-slate-100 text-slate-600 text-sm border-l border-slate-300">.<?php echo e(config('app.tenant_domain')); ?></span>
            </div>
            <button class="mt-3 w-full px-4 py-2 rounded-md bg-indigo-600 text-white text-sm">Go to workspace</button>
        </form>
    </div>

    
    <div class="mt-10 max-w-3xl mx-auto">
        <?php if($stripeReady): ?>
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                <strong>Stripe is live.</strong> Pro and Enterprise upgrades go through real Stripe Checkout — test cards work with <code class="bg-emerald-100 px-1 rounded">4242 4242 4242 4242</code>.
            </div>
        <?php else: ?>
            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm">
                <strong>Dev mode:</strong> No Stripe keys configured. Signups create workspaces directly and plan switches happen instantly. To enable real billing, set <code class="bg-amber-100 px-1 rounded">STRIPE_KEY</code>, <code class="bg-amber-100 px-1 rounded">STRIPE_SECRET</code>, and <code class="bg-amber-100 px-1 rounded">STRIPE_PRICE_PRO</code> in <code class="bg-amber-100 px-1 rounded">.env</code>.
            </div>
        <?php endif; ?>
    </div>

    <section class="mt-12 max-w-3xl mx-auto">
        <h2 class="text-xl font-bold mb-4">Frequently asked</h2>
        <div class="space-y-3">
            <details class="bg-white rounded-lg border border-slate-200 p-4">
                <summary class="font-medium cursor-pointer">What happens when I hit a limit?</summary>
                <p class="mt-2 text-sm text-slate-600">The action is blocked server-side with a clear message. Existing data is never touched. Upgrade and you can immediately continue.</p>
            </details>
            <details class="bg-white rounded-lg border border-slate-200 p-4">
                <summary class="font-medium cursor-pointer">Can I downgrade at any time?</summary>
                <p class="mt-2 text-sm text-slate-600">Yes — from your workspace's Billing page. Downgrading takes effect at the end of the current billing period, never mid-cycle.</p>
            </details>
            <details class="bg-white rounded-lg border border-slate-200 p-4">
                <summary class="font-medium cursor-pointer">Do you store payment details?</summary>
                <p class="mt-2 text-sm text-slate-600">No. All card data goes directly to Stripe — we never see or store it. We keep only a tokenized customer ID and the last 4 digits for receipts.</p>
            </details>
            <details class="bg-white rounded-lg border border-slate-200 p-4">
                <summary class="font-medium cursor-pointer">Is each workspace really isolated?</summary>
                <p class="mt-2 text-sm text-slate-600">Yes — at the database level, not just by row filtering. Every workspace gets its own MySQL database. There is literally no shared table where data could leak across customers.</p>
            </details>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.central', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/central/billing.blade.php ENDPATH**/ ?>