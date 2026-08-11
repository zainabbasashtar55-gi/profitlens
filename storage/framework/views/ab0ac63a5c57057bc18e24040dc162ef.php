<?php $__env->startSection('title', 'Invoices'); ?>

<?php
    $fmt = fn ($n) => '$' . number_format((float) $n, 2);
    $statusChip = function (\App\Models\Invoice $inv) {
        $label = $inv->statusLabel();
        $cls = match ($label) {
            'paid'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'sent'    => 'bg-sky-50 text-sky-700 ring-sky-200',
            'viewed'  => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'overdue' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'void'    => 'bg-slate-100 text-slate-500 ring-slate-200',
            default   => 'bg-amber-50 text-amber-700 ring-amber-200',
        };
        return '<span class="text-xs px-2 py-0.5 rounded ring-1 ring-inset capitalize ' . $cls . '">' . $label . '</span>';
    };
?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Invoices</h1>
            <p class="text-sm text-slate-600">Send branded invoices, track who's paid, get paid faster.</p>
        </div>
        <a href="<?php echo e(route('invoices.create')); ?>" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ New invoice</a>
    </div>

    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs font-semibold uppercase text-slate-500">Outstanding</div>
            <div class="mt-1 text-xl font-bold"><?php echo e($fmt($counts['outstanding'])); ?></div>
            <div class="text-xs text-slate-500">across <?php echo e($counts['sent']); ?> open invoices</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs font-semibold uppercase text-slate-500">Paid this month</div>
            <div class="mt-1 text-xl font-bold text-emerald-700"><?php echo e($fmt($counts['paid_mtd'])); ?></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs font-semibold uppercase text-slate-500">Overdue</div>
            <div class="mt-1 text-xl font-bold <?php echo e($counts['overdue'] > 0 ? 'text-rose-700' : 'text-slate-700'); ?>"><?php echo e($counts['overdue']); ?></div>
            <div class="text-xs text-slate-500">need a reminder</div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="text-xs font-semibold uppercase text-slate-500">Drafts</div>
            <div class="mt-1 text-xl font-bold text-slate-700"><?php echo e($counts['draft']); ?></div>
            <div class="text-xs text-slate-500">not sent yet</div>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-3 mb-4 flex flex-wrap gap-2">
        <input name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Search by # or customer" class="flex-1 min-w-[200px] rounded-md border border-slate-300 px-3 py-1.5 text-sm">
        <select name="status" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All statuses</option>
            <?php $__currentLoopData = ['draft', 'sent', 'viewed', 'paid', 'overdue', 'void']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($s); ?>" <?php echo e(($filters['status'] ?? '') === $s ? 'selected' : ''); ?>><?php echo e(ucfirst($s)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="customer_id" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <option value="">All customers</option>
            <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($c->id); ?>" <?php echo e((int)($filters['customer_id'] ?? 0) === $c->id ? 'selected' : ''); ?>><?php echo e($c->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-sm">Filter</button>
        <a href="<?php echo e(route('invoices.index')); ?>" class="px-3 py-1.5 rounded-md text-slate-500 text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Invoice #</th>
                    <th class="text-left px-5 py-2">Customer</th>
                    <th class="text-left px-5 py-2">Status</th>
                    <th class="text-left px-5 py-2">Issued</th>
                    <th class="text-left px-5 py-2">Due</th>
                    <th class="text-right px-5 py-2">Total</th>
                    <th class="text-right px-5 py-2">Balance</th>
                    <th class="text-right px-5 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono text-xs text-slate-700">
                            <a href="<?php echo e(route('invoices.show', $inv)); ?>" class="text-indigo-600 hover:underline"><?php echo e($inv->invoice_number); ?></a>
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-medium"><?php echo e($inv->customer->name); ?></div>
                            <?php if($inv->customer->company): ?>
                                <div class="text-xs text-slate-500"><?php echo e($inv->customer->company); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3"><?php echo $statusChip($inv); ?></td>
                        <td class="px-5 py-3 text-slate-700"><?php echo e($inv->issue_date->format('M j, Y')); ?></td>
                        <td class="px-5 py-3 text-slate-700">
                            <?php echo e($inv->due_date->format('M j, Y')); ?>

                            <?php if($inv->isOverdue()): ?>
                                <div class="text-xs text-rose-600"><?php echo e($inv->daysOverdue()); ?>d overdue</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right font-mono"><?php echo e($fmt($inv->total)); ?></td>
                        <td class="px-5 py-3 text-right font-mono <?php echo e($inv->balanceDue() > 0 ? 'text-slate-900' : 'text-emerald-600'); ?>"><?php echo e($fmt($inv->balanceDue())); ?></td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="<?php echo e(route('invoices.show', $inv)); ?>" class="text-xs text-indigo-600 hover:underline">View</a>
                            <?php if($inv->status === \App\Models\Invoice::STATUS_DRAFT): ?>
                                <form method="POST" action="<?php echo e(route('invoices.send', $inv)); ?>" class="inline" onsubmit="return confirm('Email this invoice to <?php echo e($inv->customer->email); ?>?')">
                                    <?php echo csrf_field(); ?>
                                    <button class="text-xs text-emerald-700 hover:underline ml-2">Send</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">No invoices yet. <a href="<?php echo e(route('invoices.create')); ?>" class="text-indigo-600">Create your first invoice</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($invoices->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/invoices/index.blade.php ENDPATH**/ ?>