<?php $__env->startSection('title', $product->exists ? 'Edit product' : 'New product'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold mb-1"><?php echo e($product->exists ? 'Edit product' : 'New product'); ?></h1>
        <p class="text-sm text-slate-600 mb-6">Cost price is captured at sale time, so future cost changes don't rewrite history.</p>

        <?php if($errors->any()): ?>
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
                <ul class="list-disc list-inside"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e($product->exists ? route('products.update', $product) : route('products.store')); ?>"
              class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
            <?php echo csrf_field(); ?>
            <?php if($product->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                    <input name="name" required value="<?php echo e(old('name', $product->name)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">SKU</label>
                    <input name="sku" value="<?php echo e(old('sku', $product->sku)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cost price *</label>
                    <input name="cost_price" type="number" step="0.01" min="0" required value="<?php echo e(old('cost_price', $product->cost_price)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sell price *</label>
                    <input name="sell_price" type="number" step="0.01" min="0" required value="<?php echo e(old('sell_price', $product->sell_price)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2"><?php echo e(old('description', $product->description)); ?></textarea>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" <?php echo e(old('active', $product->active) ? 'checked' : ''); ?> class="rounded border-slate-300">
                Active (available for new sales)
            </label>

            <div class="flex gap-2 pt-2">
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700"><?php echo e($product->exists ? 'Save changes' : 'Create product'); ?></button>
                <a href="<?php echo e(route('products.index')); ?>" class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm">Cancel</a>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/products/form.blade.php ENDPATH**/ ?>