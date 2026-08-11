<?php $__env->startSection('title', 'Reset password'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-md mx-auto bg-white rounded-lg border border-slate-200 p-8">
        <h1 class="text-2xl font-bold">Set a new password</h1>
        <p class="mt-1 text-sm text-slate-600">Pick something strong — at least 8 characters.</p>

        <?php if($errors->any()): ?>
            <div class="mt-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm">
                <ul class="list-disc list-inside">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('password.update')); ?>" class="mt-6 space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo e($token); ?>">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input name="email" type="email" required value="<?php echo e(old('email', $email)); ?>"
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/auth/reset-password.blade.php ENDPATH**/ ?>