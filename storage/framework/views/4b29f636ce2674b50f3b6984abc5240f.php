<?php $__env->startSection('title', 'Activity log'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Activity log</h1>
        <p class="text-sm text-slate-600">Every change in this workspace is recorded here. Audit trail kept indefinitely.</p>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-3 mb-4 flex gap-2">
        <select name="type" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All event types</option>
            <option value="Sale"     <?php echo e(($filters['type'] ?? '') === 'Sale'     ? 'selected' : ''); ?>>Sales</option>
            <option value="Expense"  <?php echo e(($filters['type'] ?? '') === 'Expense'  ? 'selected' : ''); ?>>Expenses</option>
            <option value="Customer" <?php echo e(($filters['type'] ?? '') === 'Customer' ? 'selected' : ''); ?>>Customers</option>
            <option value="Product"  <?php echo e(($filters['type'] ?? '') === 'Product'  ? 'selected' : ''); ?>>Products</option>
            <option value="User"     <?php echo e(($filters['type'] ?? '') === 'User'     ? 'selected' : ''); ?>>Users</option>
        </select>
        <button class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-sm">Filter</button>
        <a href="<?php echo e(route('activity.index')); ?>" class="px-3 py-1.5 rounded-md text-slate-500 text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $iconCls = match ($a->event) {
                    'created' => 'bg-emerald-100 text-emerald-700',
                    'updated' => 'bg-sky-100 text-sky-700',
                    'deleted' => 'bg-rose-100 text-rose-700',
                    default   => 'bg-slate-100 text-slate-700',
                };
                $icon = match ($a->event) {
                    'created' => '+',
                    'updated' => '✎',
                    'deleted' => '✕',
                    default   => '●',
                };
            ?>
            <div class="px-5 py-4 border-b border-slate-100 last:border-b-0 flex items-start gap-4">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full flex-shrink-0 font-semibold <?php echo e($iconCls); ?>"><?php echo e($icon); ?></span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm">
                        <span class="font-medium"><?php echo e($a->causer?->name ?? 'System'); ?></span>
                        <span class="text-slate-600"><?php echo e($a->description); ?></span>
                    </div>
                    <?php if($a->properties->isNotEmpty() && $a->event === 'updated'): ?>
                        <?php $old = $a->properties->get('old', []); $new = $a->properties->get('attributes', []); ?>
                        <?php if(!empty($new)): ?>
                            <details class="mt-1 text-xs text-slate-500">
                                <summary class="cursor-pointer hover:text-slate-700">View changes</summary>
                                <table class="mt-2 ml-1 text-xs">
                                    <?php $__currentLoopData = $new; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="pr-3 font-mono text-slate-500"><?php echo e($key); ?>:</td>
                                            <td class="text-rose-600 line-through"><?php echo e(is_array($old[$key] ?? null) ? json_encode($old[$key]) : ($old[$key] ?? '∅')); ?></td>
                                            <td class="px-2 text-slate-400">→</td>
                                            <td class="text-emerald-700"><?php echo e(is_array($val) ? json_encode($val) : $val); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </table>
                            </details>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="text-xs text-slate-400 mt-0.5">
                        <?php echo e($a->created_at->diffForHumans()); ?>

                        <span class="text-slate-300">·</span>
                        <?php echo e($a->created_at->format('M j, Y g:i A')); ?>

                        <?php if($a->subject_type): ?>
                            <span class="text-slate-300">·</span>
                            <span class="text-slate-400"><?php echo e(class_basename($a->subject_type)); ?>#<?php echo e($a->subject_id); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="px-5 py-12 text-center text-sm text-slate-500">
                No activity yet. Once you record sales, log expenses, or invite teammates, you'll see a full history here.
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4"><?php echo e($activities->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/activity/index.blade.php ENDPATH**/ ?>