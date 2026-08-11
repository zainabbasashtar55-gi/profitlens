<?php
    $fmt0 = fn ($n) => '$' . number_format((float) $n, 0);
?>

<div data-goal-wrap data-goal-target="<?php echo e($goalProgress['target'] ?? 0); ?>">
    <?php if($goalProgress): ?>
        <div class="flex items-baseline justify-between mb-1">
            <div class="text-2xl font-bold" data-goal-current><?php echo e($fmt0($goalProgress['current'])); ?></div>
            <div class="text-sm text-slate-500">of <span data-goal-target-label><?php echo e($fmt0($goalProgress['target'])); ?></span></div>
        </div>
        <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
            <div data-goal-bar
                 class="h-full rounded-full transition-all duration-700 <?php echo e($goalProgress['on_track'] ? 'bg-emerald-500' : 'bg-amber-500'); ?>"
                 style="width: <?php echo e(min(100, $goalProgress['pct'])); ?>%"></div>
        </div>
        <div class="mt-1.5 flex items-center justify-between text-xs">
            <span class="font-medium" data-goal-pct><?php echo e($goalProgress['pct']); ?>%</span>
            <span class="text-slate-500"><?php echo e($goalProgress['period_label']); ?></span>
        </div>
        <div class="mt-1 text-xs">
            <span data-goal-chip class="inline-block px-1.5 py-0.5 rounded <?php echo e($goalProgress['on_track'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'); ?>">
                <?php echo e($goalProgress['on_track'] ? 'On track' : 'Behind pace'); ?>

            </span>
            <span class="text-slate-500" data-goal-remaining><?php echo e($fmt0($goalProgress['remaining'])); ?> to go</span>
        </div>
    <?php else: ?>
        <p class="text-sm text-slate-500 mb-3">No profit goal set for this month. Set one to track progress as sales come in.</p>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('goals.store')); ?>" class="mt-3 flex gap-2">
        <?php echo csrf_field(); ?>
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
            <input name="target_amount" type="number" step="1" min="0" required
                   value="<?php echo e($goalProgress['target'] ?? ''); ?>"
                   placeholder="10000"
                   class="w-full rounded-md border border-slate-300 pl-6 pr-3 py-2 text-sm">
        </div>
        <button class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
            <?php echo e($goalProgress ? 'Update' : 'Set goal'); ?>

        </button>
    </form>
    <?php $__errorArgs = ['target_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-rose-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH D:\profitlens\resources\views/tenant/insights/_goal.blade.php ENDPATH**/ ?>