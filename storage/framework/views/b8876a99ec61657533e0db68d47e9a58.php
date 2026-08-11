<?php $__env->startSection('title', 'Record sale'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-4xl">
        <h1 class="text-2xl font-bold mb-1">Record a sale</h1>
        <p class="text-sm text-slate-600 mb-6">Profit is calculated per line item using the cost price at the time of sale.</p>

        <?php if($errors->any()): ?>
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
                <ul class="list-disc list-inside"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('sales.store')); ?>" class="bg-white rounded-lg border border-slate-200 p-6 space-y-5" x-data="saleForm()">
            <?php echo csrf_field(); ?>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date *</label>
                    <input name="sale_date" type="date" required
                           min="<?php echo e(now()->subYears(5)->toDateString()); ?>"
                           max="<?php echo e(now()->toDateString()); ?>"
                           value="<?php echo e(old('sale_date', now()->toDateString())); ?>"
                           class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
                    <select name="customer_id" class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="">— walk-in / no customer —</option>
                        <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c->id); ?>" <?php echo e(old('customer_id') == $c->id ? 'selected' : ''); ?>><?php echo e($c->name); ?><?php echo e($c->company ? ' ('.$c->company.')' : ''); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status *</label>
                    <select name="status" class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="paid">Paid</option>
                        <option value="draft">Draft</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-semibold">Line items</label>
                    <button type="button" @click="addRow()" class="text-xs px-2 py-1 rounded border border-slate-300">+ Add row</button>
                </div>

                <div class="border border-slate-200 rounded-md overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="text-left px-3 py-2">Product</th>
                                <th class="text-right px-3 py-2 w-20">Qty</th>
                                <th class="text-right px-3 py-2 w-28">Unit price</th>
                                <th class="text-right px-3 py-2 w-28">Unit cost</th>
                                <th class="text-right px-3 py-2 w-28">Profit</th>
                                <th class="w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-2">
                                        <select :name="`items[${idx}][product_id]`" x-model="item.product_id" @change="pickProduct(idx)" class="w-full rounded border border-slate-300 px-2 py-1 text-sm">
                                            <option value="">— custom item —</option>
                                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($p->id); ?>" data-name="<?php echo e($p->name); ?>" data-cost="<?php echo e($p->cost_price); ?>" data-price="<?php echo e($p->sell_price); ?>"><?php echo e($p->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <input :name="`items[${idx}][product_name]`" x-model="item.product_name" placeholder="Custom name" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-xs" x-show="!item.product_id">
                                    </td>
                                    <td class="px-3 py-2"><input :name="`items[${idx}][quantity]`" x-model.number="item.quantity" type="number" min="1" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right"></td>
                                    <td class="px-3 py-2"><input :name="`items[${idx}][unit_price]`" x-model.number="item.unit_price" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right font-mono"></td>
                                    <td class="px-3 py-2"><input :name="`items[${idx}][unit_cost]`" x-model.number="item.unit_cost" type="number" step="0.01" min="0" class="w-full rounded border border-slate-300 px-2 py-1 text-sm text-right font-mono"></td>
                                    <td class="px-3 py-2 text-right font-mono text-emerald-700" x-text="'$' + ((item.quantity * (item.unit_price - item.unit_cost)) || 0).toFixed(2)"></td>
                                    <td class="px-3 py-2 text-right"><button type="button" @click="items.splice(idx, 1)" class="text-rose-500 text-xs">✕</button></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 text-sm">
                            <tr class="border-t border-slate-200">
                                <td colspan="2" class="px-3 py-2 text-right text-slate-500">Revenue</td>
                                <td class="px-3 py-2 text-right font-mono font-semibold" x-text="'$' + revenue().toFixed(2)"></td>
                                <td class="px-3 py-2 text-right text-slate-500">Profit</td>
                                <td class="px-3 py-2 text-right font-mono font-semibold text-emerald-700" x-text="'$' + profit().toFixed(2)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2"><?php echo e(old('notes')); ?></textarea>
            </div>

            <div class="flex gap-2">
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">Record sale</button>
                <a href="<?php echo e(route('sales.index')); ?>" class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function saleForm() {
            return {
                items: [{ product_id: '', product_name: '', quantity: 1, unit_price: 0, unit_cost: 0 }],
                addRow() { this.items.push({ product_id: '', product_name: '', quantity: 1, unit_price: 0, unit_cost: 0 }); },
                pickProduct(idx) {
                    const sel = document.querySelectorAll('select[name^="items"]')[idx];
                    const opt = sel.selectedOptions[0];
                    if (opt && opt.dataset.name) {
                        this.items[idx].product_name = opt.dataset.name;
                        this.items[idx].unit_cost   = parseFloat(opt.dataset.cost);
                        this.items[idx].unit_price  = parseFloat(opt.dataset.price);
                    }
                },
                revenue() { return this.items.reduce((s, i) => s + (i.quantity * i.unit_price || 0), 0); },
                profit()  { return this.items.reduce((s, i) => s + (i.quantity * (i.unit_price - i.unit_cost) || 0), 0); },
            };
        }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/sales/form.blade.php ENDPATH**/ ?>