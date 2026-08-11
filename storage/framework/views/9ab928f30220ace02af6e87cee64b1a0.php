<?php $__env->startSection('title', $invoice->exists ? 'Edit invoice ' . $invoice->invoice_number : 'New invoice'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-4xl">
        <h1 class="text-2xl font-bold mb-1"><?php echo e($invoice->exists ? 'Edit ' . $invoice->invoice_number : 'New invoice'); ?></h1>
        <p class="text-sm text-slate-600 mb-6">Items, taxes, and totals recalculate automatically as you type.</p>

        <?php if($errors->any()): ?>
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
                <ul class="list-disc list-inside"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="<?php echo e($invoice->exists ? route('invoices.update', $invoice) : route('invoices.store')); ?>"
              class="bg-white rounded-lg border border-slate-200 p-6 space-y-5"
              id="invoice-form">
            <?php echo csrf_field(); ?>
            <?php if($invoice->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
                    <select name="customer_id" required class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="">— pick a customer —</option>
                        <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c->id); ?>" <?php echo e(old('customer_id', $invoice->customer_id) == $c->id ? 'selected' : ''); ?>>
                                <?php echo e($c->name); ?><?php echo e($c->company ? ' · ' . $c->company : ''); ?><?php echo e(! $c->email ? ' (no email)' : ''); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Customers without an email can't be sent invoices. <a href="<?php echo e(route('customers.create')); ?>" class="text-indigo-600 hover:underline">+ Add customer</a></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                    <input name="currency" value="<?php echo e(old('currency', $invoice->currency ?? 'USD')); ?>" maxlength="3" class="w-full rounded-md border border-slate-300 px-3 py-2 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Issue date *</label>
                    <input type="date" name="issue_date" required value="<?php echo e(old('issue_date', $invoice->issue_date instanceof \Carbon\Carbon ? $invoice->issue_date->toDateString() : $invoice->issue_date)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Due date *</label>
                    <input type="date" name="due_date" required value="<?php echo e(old('due_date', $invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date->toDateString() : $invoice->due_date)); ?>" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Payment terms</label>
                    <input name="payment_terms" value="<?php echo e(old('payment_terms', $invoice->payment_terms)); ?>" placeholder="e.g. Net 14" class="w-full rounded-md border border-slate-300 px-3 py-2">
                </div>
            </div>

            
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold text-sm">Line items</h2>
                    <button type="button" id="add-row" class="text-xs text-indigo-600 hover:underline">+ Add line</button>
                </div>
                <table class="w-full text-sm" id="items-table">
                    <thead class="text-xs text-slate-500">
                        <tr>
                            <th class="text-left pb-1 font-medium">Description</th>
                            <th class="text-right pb-1 font-medium w-20">Qty</th>
                            <th class="text-right pb-1 font-medium w-28">Unit price</th>
                            <th class="text-right pb-1 font-medium w-24">Tax %</th>
                            <th class="text-right pb-1 font-medium w-28">Subtotal</th>
                            <th class="w-6"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        <?php
                            $rows = old('items', $invoice->items?->map(fn ($i) => [
                                'description' => $i->description,
                                'quantity'    => (float) $i->quantity,
                                'unit_price'  => (float) $i->unit_price,
                                'unit_cost'   => (float) $i->unit_cost,
                                'tax_rate'    => (float) $i->tax_rate,
                                'product_id'  => $i->product_id,
                            ])->all() ?? []);
                            if (empty($rows)) {
                                $rows = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'unit_cost' => 0, 'tax_rate' => 0, 'product_id' => null]];
                            }
                        ?>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-t border-slate-100" data-row>
                                <td class="py-2 pr-2">
                                    <input name="items[<?php echo e($i); ?>][description]" required value="<?php echo e($row['description'] ?? ''); ?>"
                                           class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"
                                           list="product-list" data-field="description">
                                    <input type="hidden" name="items[<?php echo e($i); ?>][product_id]" value="<?php echo e($row['product_id'] ?? ''); ?>" data-field="product_id">
                                    <input type="hidden" name="items[<?php echo e($i); ?>][unit_cost]"  value="<?php echo e($row['unit_cost'] ?? 0); ?>"  data-field="unit_cost">
                                </td>
                                <td class="py-2 pr-2 text-right">
                                    <input type="number" step="0.001" min="0.001" required
                                           name="items[<?php echo e($i); ?>][quantity]" value="<?php echo e($row['quantity'] ?? 1); ?>"
                                           class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono text-right" data-field="quantity">
                                </td>
                                <td class="py-2 pr-2 text-right">
                                    <input type="number" step="0.01" min="0" required
                                           name="items[<?php echo e($i); ?>][unit_price]" value="<?php echo e($row['unit_price'] ?? 0); ?>"
                                           class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono text-right" data-field="unit_price">
                                </td>
                                <td class="py-2 pr-2 text-right">
                                    <input type="number" step="0.01" min="0" max="100"
                                           name="items[<?php echo e($i); ?>][tax_rate]" value="<?php echo e($row['tax_rate'] ?? 0); ?>"
                                           class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono text-right" data-field="tax_rate">
                                </td>
                                <td class="py-2 pr-2 text-right font-mono text-slate-700" data-field="line_subtotal">$0.00</td>
                                <td class="py-2 text-right">
                                    <button type="button" class="text-rose-500 hover:text-rose-700 text-sm" data-remove>×</button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="text-sm">
                        <tr class="border-t border-slate-200">
                            <td colspan="4" class="text-right py-2 pr-2 text-slate-600">Subtotal</td>
                            <td class="text-right py-2 pr-2 font-mono" id="totals-subtotal">$0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right py-1 pr-2 text-slate-600">Tax</td>
                            <td class="text-right py-1 pr-2 font-mono" id="totals-tax">$0.00</td>
                            <td></td>
                        </tr>
                        <tr class="border-t border-slate-200">
                            <td colspan="4" class="text-right py-2 pr-2 font-semibold">Total</td>
                            <td class="text-right py-2 pr-2 font-mono font-bold text-base" id="totals-total">$0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                
                <datalist id="product-list">
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option data-id="<?php echo e($p->id); ?>" data-price="<?php echo e((float) $p->sell_price); ?>" data-cost="<?php echo e((float) $p->cost_price); ?>" value="<?php echo e($p->name); ?>"></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </datalist>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes for the customer</label>
                <textarea name="notes" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Thanks for your business!"><?php echo e(old('notes', $invoice->notes)); ?></textarea>
            </div>

            <div class="flex gap-2 pt-2">
                <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                    <?php echo e($invoice->exists ? 'Save changes' : 'Save as draft'); ?>

                </button>
                <a href="<?php echo e($invoice->exists ? route('invoices.show', $invoice) : route('invoices.index')); ?>" class="px-4 py-2 rounded-md border border-slate-300 bg-white text-sm">Cancel</a>
            </div>
        </form>
    </div>

    
    <template id="row-template">
        <tr class="border-t border-slate-100" data-row>
            <td class="py-2 pr-2">
                <input name="items[__i__][description]" required class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" list="product-list" data-field="description">
                <input type="hidden" name="items[__i__][product_id]" data-field="product_id">
                <input type="hidden" name="items[__i__][unit_cost]" value="0" data-field="unit_cost">
            </td>
            <td class="py-2 pr-2 text-right">
                <input type="number" step="0.001" min="0.001" required name="items[__i__][quantity]" value="1" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono text-right" data-field="quantity">
            </td>
            <td class="py-2 pr-2 text-right">
                <input type="number" step="0.01" min="0" required name="items[__i__][unit_price]" value="0" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono text-right" data-field="unit_price">
            </td>
            <td class="py-2 pr-2 text-right">
                <input type="number" step="0.01" min="0" max="100" name="items[__i__][tax_rate]" value="0" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono text-right" data-field="tax_rate">
            </td>
            <td class="py-2 pr-2 text-right font-mono text-slate-700" data-field="line_subtotal">$0.00</td>
            <td class="py-2 text-right"><button type="button" class="text-rose-500 hover:text-rose-700 text-sm" data-remove>×</button></td>
        </tr>
    </template>

    <script>
        (function () {
            const body  = document.getElementById('items-body');
            const tmpl  = document.getElementById('row-template');
            const subEl = document.getElementById('totals-subtotal');
            const taxEl = document.getElementById('totals-tax');
            const totEl = document.getElementById('totals-total');
            const fmt = (n) => '$' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function recalc() {
                let subtotal = 0, tax = 0;
                body.querySelectorAll('[data-row]').forEach(row => {
                    const qty = parseFloat(row.querySelector('[data-field="quantity"]').value) || 0;
                    const px  = parseFloat(row.querySelector('[data-field="unit_price"]').value) || 0;
                    const tr  = parseFloat(row.querySelector('[data-field="tax_rate"]').value) || 0;
                    const line = Math.round(qty * px * 100) / 100;
                    const lineTax = Math.round(line * (tr / 100) * 100) / 100;
                    subtotal += line;
                    tax += lineTax;
                    row.querySelector('[data-field="line_subtotal"]').textContent = fmt(line);
                });
                subEl.textContent = fmt(subtotal);
                taxEl.textContent = fmt(tax);
                totEl.textContent = fmt(subtotal + tax);
            }

            function reindex() {
                body.querySelectorAll('[data-row]').forEach((row, i) => {
                    row.querySelectorAll('[name^="items["]').forEach(input => {
                        input.name = input.name.replace(/items\[\d+\]/, `items[${i}]`);
                    });
                });
            }

            // Add row
            document.getElementById('add-row').addEventListener('click', () => {
                const clone = tmpl.content.firstElementChild.cloneNode(true);
                clone.querySelectorAll('[name]').forEach(input => {
                    input.name = input.name.replace(/__i__/g, body.children.length);
                });
                body.appendChild(clone);
                recalc();
            });

            // Remove row
            body.addEventListener('click', (e) => {
                if (e.target.matches('[data-remove]')) {
                    if (body.children.length <= 1) return;
                    e.target.closest('[data-row]').remove();
                    reindex();
                    recalc();
                }
            });

            // Live recalc on any change
            body.addEventListener('input', recalc);

            // Product autocomplete: when description matches a product, snap unit_price + unit_cost
            body.addEventListener('change', (e) => {
                if (e.target.matches('[data-field="description"]')) {
                    const opt = document.querySelector(`#product-list option[value="${CSS.escape(e.target.value)}"]`);
                    if (opt) {
                        const row = e.target.closest('[data-row]');
                        row.querySelector('[data-field="product_id"]').value = opt.dataset.id || '';
                        row.querySelector('[data-field="unit_price"]').value = opt.dataset.price || 0;
                        row.querySelector('[data-field="unit_cost"]').value  = opt.dataset.cost  || 0;
                        recalc();
                    }
                }
            });

            recalc();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/invoices/form.blade.php ENDPATH**/ ?>