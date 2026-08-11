<?php $__env->startSection('title', 'Workspace settings'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Workspace settings</h1>
        <p class="text-sm text-slate-600">Owner-only controls for renaming, moving, and deleting this workspace.</p>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm mb-4">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
            <ul class="list-disc list-inside"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </div>
    <?php endif; ?>

    <div class="space-y-6 max-w-3xl">

        
        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-6 py-4 border-b border-slate-200">
                <h2 class="font-semibold">General</h2>
                <p class="mt-1 text-xs text-slate-500">Public-facing details for your workspace.</p>
            </header>
            <form method="POST" action="<?php echo e(route('settings.update-name')); ?>" class="px-6 py-5 space-y-4">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Workspace name</label>
                    <input name="name" required maxlength="120" value="<?php echo e(old('name', $tenant->name)); ?>"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-slate-500">Shown in the sidebar, invitation emails, and your team page.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Workspace ID</label>
                    <input value="<?php echo e($tenant->id); ?>" disabled
                           class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600 font-mono text-sm">
                    <p class="mt-1 text-xs text-slate-500">Internal identifier. Cannot be changed.</p>
                </div>
                <div class="flex justify-end">
                    <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Save changes</button>
                </div>
            </form>
        </section>

        
        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-6 py-4 border-b border-slate-200">
                <h2 class="font-semibold">Subdomain</h2>
                <p class="mt-1 text-xs text-slate-500">The URL your team uses to access this workspace.</p>
            </header>
            <form method="POST" action="<?php echo e(route('settings.update-subdomain')); ?>" class="px-6 py-5 space-y-4">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Workspace URL</label>
                    <div class="flex rounded-md border border-slate-300 overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                        <span class="px-3 py-2 bg-slate-50 text-slate-500 text-sm border-r border-slate-300">https://</span>
                        <input name="subdomain" required value="<?php echo e(old('subdomain', $subdomain)); ?>" pattern="[a-z0-9-]+" maxlength="63"
                               class="flex-1 px-3 py-2 focus:outline-none">
                        <span class="px-3 py-2 bg-slate-50 text-slate-600 text-sm border-l border-slate-300">.<?php echo e($tenantRoot); ?></span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Lowercase letters, numbers, and dashes only.</p>
                </div>
                <div class="rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-amber-800 text-xs">
                    <strong>Heads up:</strong> changing your subdomain logs everyone out and breaks any unaccepted invitation links + active reset-password emails. Existing API tokens still work.
                </div>
                <div class="flex justify-end">
                    <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Change subdomain</button>
                </div>
            </form>
        </section>

        
        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-6 py-4 border-b border-slate-200">
                <h2 class="font-semibold">Plan &amp; billing</h2>
            </header>
            <div class="px-6 py-5 flex items-center justify-between">
                <div>
                    <div class="text-sm text-slate-700">Current plan: <span class="font-medium capitalize"><?php echo e($tenant->plan ?? 'free'); ?></span></div>
                    <div class="text-xs text-slate-500 mt-1">Manage subscription, change plan, and view usage on the billing page.</div>
                </div>
                <a href="<?php echo e(route('billing.index')); ?>" class="px-4 py-2 rounded-md border border-slate-300 text-sm hover:bg-slate-50">Go to billing →</a>
            </div>
        </section>

        
        <section class="bg-white rounded-lg border-2 border-rose-200 overflow-hidden">
            <header class="px-6 py-4 border-b border-rose-200 bg-rose-50">
                <h2 class="font-semibold text-rose-900">Danger zone</h2>
                <p class="mt-1 text-xs text-rose-700">Permanent and irreversible actions. Read carefully.</p>
            </header>

            <div class="px-6 py-5">
                <details class="group">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <div>
                            <div class="font-medium text-rose-900">Delete this workspace</div>
                            <div class="text-xs text-rose-600 mt-0.5">Drops your database, removes all team members, and cancels billing. There is no undo.</div>
                        </div>
                        <span class="text-rose-500 text-xs group-open:hidden">▸ Click to expand</span>
                        <span class="text-rose-500 text-xs hidden group-open:inline">▾ Collapse</span>
                    </summary>

                    <form method="POST" action="<?php echo e(route('settings.destroy')); ?>" class="mt-4 space-y-3"
                          onsubmit="return confirm('Are you absolutely sure? This drops the database and cannot be undone.')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>

                        <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-xs space-y-1">
                            <div>This will permanently:</div>
                            <ul class="list-disc list-inside ml-2 space-y-0.5">
                                <li>Drop the <code class="bg-rose-100 px-1 rounded">tenant<?php echo e($tenant->id); ?></code> database</li>
                                <li>Remove all sales, customers, products, expenses, and receipt uploads</li>
                                <li>Sign out every team member</li>
                                <li>Cancel any active Stripe subscription</li>
                                <li>Revoke every API token</li>
                            </ul>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-rose-900 mb-1">
                                Type <strong><?php echo e($tenant->name); ?></strong> to confirm
                            </label>
                            <input name="confirmation" autocomplete="off"
                                   class="w-full rounded-md border border-rose-300 px-3 py-2 focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                        </div>

                        <button class="px-4 py-2 rounded-md bg-rose-600 text-white text-sm font-medium hover:bg-rose-700">
                            Delete workspace permanently
                        </button>
                    </form>
                </details>
            </div>
        </section>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/settings/index.blade.php ENDPATH**/ ?>