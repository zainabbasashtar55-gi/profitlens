<?php $__env->startSection('title', 'Forgot password'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-md mx-auto bg-white rounded-lg border border-slate-200 p-8">
        <h1 class="text-2xl font-bold">Forgot your password?</h1>
        <p class="mt-1 text-sm text-slate-600">Enter the email you use for <strong><?php echo e(tenant('name')); ?></strong> and we'll send a reset link.</p>

        <?php if(session('status')): ?>
            <div class="mt-4 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="mt-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('password.email')); ?>" class="mt-6 space-y-4">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input name="email" type="email" required autofocus value="<?php echo e(old('email')); ?>"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <button class="w-full px-4 py-2.5 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                Send reset link
            </button>
        </form>

        <div class="mt-4 text-center text-sm">
            <a href="<?php echo e(route('tenant.login')); ?>" class="text-slate-600 hover:text-slate-900">← Back to log in</a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/auth/forgot-password.blade.php ENDPATH**/ ?>