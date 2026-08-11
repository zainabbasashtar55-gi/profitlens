<?php $__env->startSection('title', 'Customers'); ?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Customers</h1>
            <p class="text-sm text-slate-600"><?php echo e($customers->total()); ?> <?php echo e(Str::plural('customer', $customers->total())); ?></p>
        </div>
        <a href="<?php echo e(route('customers.create')); ?>" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Add customer</a>
    </div>

    <form method="GET" class="mb-4">
        <input name="q" value="<?php echo e($search); ?>" placeholder="Search name, email or company…"
               class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm">
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Name</th>
                    <th class="text-left px-5 py-2">Company</th>
                    <th class="text-left px-5 py-2">Email</th>
                    <th class="text-right px-5 py-2">Sales</th>
                    <th class="text-right px-5 py-2">LTV</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3 font-medium"><?php echo e($c->name); ?></td>
                        <td class="px-5 py-3 text-slate-600"><?php echo e($c->company ?: '—'); ?></td>
                        <td class="px-5 py-3 text-slate-600"><?php echo e($c->email ?: '—'); ?></td>
                        <td class="px-5 py-3 text-right text-slate-600"><?php echo e($c->sales_count); ?></td>
                        <td class="px-5 py-3 text-right font-mono">$<?php echo e(number_format($c->lifetimeValue(), 2)); ?></td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3 text-xs">
                                <a href="<?php echo e(route('customers.edit', $c)); ?>" class="text-indigo-600 hover:underline">Edit</a>
                                <?php if(auth()->user()->hasAnyRole(['owner', 'admin'])): ?>
                                    <form method="POST" action="<?php echo e(route('customers.destroy', $c)); ?>" onsubmit="return confirm('Delete this customer?')">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button class="text-rose-600 hover:underline">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                        No customers yet. <a href="<?php echo e(route('customers.create')); ?>" class="text-indigo-600">Add your first one</a>.
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($customers->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/customers/index.blade.php ENDPATH**/ ?>