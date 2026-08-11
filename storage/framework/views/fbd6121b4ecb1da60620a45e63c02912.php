<?php $__env->startSection('title', 'Products'); ?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Products</h1>
            <p class="text-sm text-slate-600"><?php echo e($products->total()); ?> <?php echo e(Str::plural('product', $products->total())); ?></p>
        </div>
        <a href="<?php echo e(route('products.create')); ?>" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Add product</a>
    </div>

    <form method="GET" class="mb-4">
        <input name="q" value="<?php echo e($search); ?>" placeholder="Search by name or SKU…" class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm">
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Name</th>
                    <th class="text-left px-5 py-2">SKU</th>
                    <th class="text-right px-5 py-2">Cost</th>
                    <th class="text-right px-5 py-2">Price</th>
                    <th class="text-right px-5 py-2">Margin</th>
                    <th class="text-center px-5 py-2">Active</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3 font-medium"><?php echo e($p->name); ?></td>
                        <td class="px-5 py-3 text-slate-500 font-mono text-xs"><?php echo e($p->sku ?: '—'); ?></td>
                        <td class="px-5 py-3 text-right font-mono">$<?php echo e(number_format($p->cost_price, 2)); ?></td>
                        <td class="px-5 py-3 text-right font-mono">$<?php echo e(number_format($p->sell_price, 2)); ?></td>
                        <td class="px-5 py-3 text-right text-emerald-700 font-medium"><?php echo e(number_format($p->margin(), 1)); ?>%</td>
                        <td class="px-5 py-3 text-center">
                            <?php if($p->active): ?>
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                            <?php else: ?>
                                <span class="inline-block w-2 h-2 rounded-full bg-slate-300"></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3 text-xs">
                                <a href="<?php echo e(route('products.edit', $p)); ?>" class="text-indigo-600 hover:underline">Edit</a>
                                <?php if(auth()->user()->hasAnyRole(['owner', 'admin'])): ?>
                                    <form method="POST" action="<?php echo e(route('products.destroy', $p)); ?>" onsubmit="return confirm('Delete this product?')">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button class="text-rose-600 hover:underline">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">No products yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($products->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/products/index.blade.php ENDPATH**/ ?>