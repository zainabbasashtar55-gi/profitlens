<?php $__env->startSection('title', $customer->exists ? 'Edit customer' : 'New customer'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold mb-1"><?php echo e($customer->exists ? 'Edit customer' : 'New customer'); ?></h1>
        <p class="text-sm text-slate-600 mb-6">Customer records feed into the analytics dashboard (LTV, top customers).</p>

        <?php if($errors->any()): ?>
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
                <ul class="list-disc list-inside"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e($customer->exists ? route('customers.update', $customer) : route('customers.store')); ?>"
              class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <?php echo csrf_field(); ?>
            <?php if($customer->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                    <input name="name" value="<?php echo e(old('name', $customer->name)); ?>" required class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company</label>
                    <input name="company" value="<?php echo e(old('company', $customer->company)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input name="email" type="email" value="<?php echo e(old('email', $customer->email)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input name="phone" value="<?php echo e(old('phone', $customer->phone)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2"><?php echo e(old('notes', $customer->notes)); ?></textarea>
            </div>

            <div class="flex gap-2 pt-2">
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700"><?php echo e($customer->exists ? 'Save changes' : 'Create customer'); ?></button>
                <a href="<?php echo e(route('customers.index')); ?>" class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm">Cancel</a>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/customers/form.blade.php ENDPATH**/ ?>