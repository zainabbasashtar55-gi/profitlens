<?php $__env->startSection('title', 'Expenses'); ?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Expenses</h1>
            <p class="text-sm text-slate-600"><?php echo e($expenses->total()); ?> <?php echo e(Str::plural('expense', $expenses->total())); ?></p>
        </div>
        <a href="<?php echo e(route('expenses.create')); ?>" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Log expense</a>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-3 mb-4 flex gap-2">
        <select name="category_id" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All categories</option>
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cat->id); ?>" <?php echo e(($filters['category_id'] ?? '') == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <input name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Search vendor or description…" class="flex-1 max-w-sm rounded-md border border-slate-300 px-3 py-1.5 text-sm">
        <button class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-sm">Filter</button>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Date</th>
                    <th class="text-left px-5 py-2">Description</th>
                    <th class="text-left px-5 py-2">Category</th>
                    <th class="text-left px-5 py-2">Vendor</th>
                    <th class="text-right px-5 py-2">Amount</th>
                    <th class="text-center px-5 py-2">Receipt</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3"><?php echo e($e->expense_date->format('M j, Y')); ?></td>
                        <td class="px-5 py-3 font-medium">
                            <?php echo e($e->description); ?>

                            <?php if($e->recurring): ?>
                                <span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">↻ <?php echo e($e->recurring_period); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3">
                            <?php if($e->category): ?>
                                <span class="inline-flex items-center gap-1 text-xs">
                                    <span class="inline-block w-2 h-2 rounded-full" style="background:<?php echo e($e->category->color); ?>"></span>
                                    <?php echo e($e->category->name); ?>

                                </span>
                            <?php else: ?> — <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-slate-600"><?php echo e($e->vendor ?: '—'); ?></td>
                        <td class="px-5 py-3 text-right font-mono">$<?php echo e(number_format($e->amount, 2)); ?></td>
                        <td class="px-5 py-3 text-center">
                            <?php if($e->receipt_path): ?>
                                <a href="<?php echo e($e->receiptUrl()); ?>" target="_blank" class="text-indigo-600 text-xs hover:underline">View</a>
                            <?php else: ?> <span class="text-slate-300">—</span> <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3 text-xs">
                                <a href="<?php echo e(route('expenses.edit', $e)); ?>" class="text-indigo-600 hover:underline">Edit</a>
                                <?php if(auth()->user()->hasAnyRole(['owner', 'admin'])): ?>
                                    <form method="POST" action="<?php echo e(route('expenses.destroy', $e)); ?>" onsubmit="return confirm('Delete this expense?')">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button class="text-rose-600 hover:underline">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">No expenses logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($expenses->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/expenses/index.blade.php ENDPATH**/ ?>