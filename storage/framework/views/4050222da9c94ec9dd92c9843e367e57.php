<?php $__env->startSection('title', $user->name); ?>

<?php
    $fmt = fn ($n) => '$' . number_format($n, 2);
    $roleClass = fn ($role) => match ($role) {
        'owner'  => 'bg-rose-50 text-rose-700 border-rose-200',
        'admin'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'member' => 'bg-slate-50 text-slate-700 border-slate-200',
        default  => 'bg-slate-50 text-slate-700 border-slate-200',
    };
?>

<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <a href="<?php echo e(route('team.index')); ?>" class="text-sm text-indigo-600 hover:underline">← Back to team</a>
    </div>

    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold"><?php echo e($user->name); ?></h1>
                <?php if($user->id === auth()->id()): ?>
                    <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600">you</span>
                <?php endif; ?>
                <?php $__currentLoopData = $user->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="text-xs px-2 py-0.5 rounded border <?php echo e($roleClass($role->name)); ?>"><?php echo e($role->name); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <p class="mt-1 text-sm text-slate-600"><?php echo e($user->email); ?></p>
            <p class="mt-1 text-xs text-slate-500">
                Joined <?php echo e($user->created_at->format('M j, Y')); ?> ·
                <?php if($user->isOnline()): ?>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Online now</span>
                <?php elseif($user->last_seen_at): ?>
                    Last seen <?php echo e($user->last_seen_at->diffForHumans()); ?>

                    <?php if($user->last_seen_ip): ?> · from <?php echo e($user->last_seen_ip); ?> <?php endif; ?>
                <?php else: ?>
                    Never logged in
                <?php endif; ?>
                <?php if($user->hasVerifiedEmail()): ?>
                    · <span class="text-emerald-600">Email verified</span>
                <?php else: ?>
                    · <span class="text-amber-600">Email unverified</span>
                <?php endif; ?>
            </p>
        </div>

        <?php if($canManage): ?>
            <div class="flex gap-2">
                <form method="POST" action="<?php echo e(route('team.destroy', $user)); ?>"
                      onsubmit="return confirm('Remove <?php echo e($user->name); ?> from this workspace?')">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button class="px-3 py-2 rounded-md border border-rose-300 text-rose-700 text-sm hover:bg-rose-50">Remove from workspace</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Sales recorded</div>
            <div class="mt-2 text-2xl font-bold"><?php echo e($salesCount); ?></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Revenue generated</div>
            <div class="mt-2 text-2xl font-bold text-emerald-700"><?php echo e($fmt($salesRevenue)); ?></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Expenses logged</div>
            <div class="mt-2 text-2xl font-bold"><?php echo e($expensesCount); ?></div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide font-semibold text-slate-500">Total expense value</div>
            <div class="mt-2 text-2xl font-bold text-rose-700"><?php echo e($fmt($expensesTotal)); ?></div>
        </div>
    </div>

    
    <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <header class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-semibold">Recent activity by <?php echo e($user->name); ?></h2>
            <a href="<?php echo e(route('activity.index', ['user_id' => $user->id])); ?>" class="text-xs text-indigo-600 hover:underline">Full audit trail →</a>
        </header>

        <?php if($activities->isEmpty()): ?>
            <div class="px-5 py-8 text-center text-sm text-slate-500">
                No activity yet from this user.
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 text-sm">
                <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                    <li class="px-5 py-3 flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full <?php echo e($iconCls); ?> font-semibold text-xs flex-shrink-0"><?php echo e($icon); ?></span>
                        <div class="flex-1">
                            <div class="text-sm text-slate-700"><?php echo e($a->description); ?></div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                <?php echo e($a->created_at->diffForHumans()); ?> ·
                                <span class="text-slate-400"><?php echo e($a->created_at->format('M j, Y g:i A')); ?></span>
                                <?php if($a->subject_type): ?>
                                    · <?php echo e(class_basename($a->subject_type)); ?><?php if($a->subject_id): ?>#<?php echo e($a->subject_id); ?><?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/team/show.blade.php ENDPATH**/ ?>