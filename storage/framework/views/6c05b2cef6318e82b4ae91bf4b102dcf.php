<?php $__env->startSection('title', 'Log in'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-md mx-auto bg-white rounded-lg border border-slate-200 p-8">
        <h1 class="text-2xl font-bold">Log in to <?php echo e(tenant('name')); ?></h1>
        <p class="mt-1 text-sm text-slate-600">This workspace is hosted at <code class="bg-slate-100 px-1 rounded text-xs"><?php echo e(request()->getHost()); ?></code>.</p>

        <?php if($errors->any()): ?>
            <div class="mt-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('tenant.login')); ?>" class="mt-6 space-y-4">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input name="email" type="email" required autofocus value="<?php echo e(old('email', $prefillEmail ?? '')); ?>"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input name="password" type="password" required
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="rounded border-slate-300">
                    Remember me
                </label>
                <a href="<?php echo e(route('password.request')); ?>" class="text-sm text-indigo-600 hover:underline">Forgot password?</a>
            </div>

            <button type="submit" class="w-full px-4 py-2.5 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                Log in
            </button>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/login.blade.php ENDPATH**/ ?>