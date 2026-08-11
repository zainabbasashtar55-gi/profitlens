<?php $__env->startSection('title', 'Team'); ?>

<?php
    $roleClass = fn ($role) => match ($role) {
        'owner'  => 'bg-rose-50 text-rose-700 border-rose-200',
        'admin'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'member' => 'bg-slate-50 text-slate-700 border-slate-200',
        default  => 'bg-slate-50 text-slate-700 border-slate-200',
    };
?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Team</h1>
            <p class="text-sm text-slate-600"><?php echo e($users->count()); ?> <?php echo e(Str::plural('member', $users->count())); ?> · <?php echo e($pendingInvitations->count()); ?> pending <?php echo e(Str::plural('invitation', $pendingInvitations->count())); ?></p>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm mb-4">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm mb-4">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <input name="q" value="<?php echo e($search); ?>" placeholder="Search name or email…"
               class="w-full max-w-sm rounded-md border border-slate-300 px-3 py-2 text-sm">
    </form>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold">Members</h2>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-2">Name</th>
                        <th class="text-left px-5 py-2">Status</th>
                        <th class="text-left px-5 py-2 w-44">Role</th>
                        <th class="text-right px-5 py-2 w-32"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3">
                                <a href="<?php echo e(route('team.show', $u)); ?>" class="block hover:bg-slate-50 -m-3 p-3 rounded">
                                    <div class="font-medium text-indigo-700 hover:underline">
                                        <?php echo e($u->name); ?>

                                        <?php if($u->id === auth()->id()): ?><span class="ml-1 text-xs text-slate-400">(you)</span><?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-500"><?php echo e($u->email); ?></div>
                                </a>
                            </td>
                            <td class="px-5 py-3 text-xs">
                                <?php if($u->isOnline()): ?>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span class="text-emerald-700 font-medium">Online now</span>
                                    </span>
                                <?php elseif($u->last_seen_at): ?>
                                    <span class="text-slate-500">Last seen <?php echo e($u->last_seen_at->diffForHumans()); ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400 italic">Never logged in</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3">
                                <?php $userRole = $u->roles->first()?->name ?? 'member'; ?>
                                <?php if(auth()->user()->hasAnyRole(['owner', 'admin']) && $u->id !== auth()->id() && (auth()->user()->hasRole('owner') || ! $u->hasRole('owner'))): ?>
                                    <form method="POST" action="<?php echo e(route('team.update-role', $u)); ?>" class="flex gap-1">
                                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                        <select name="role" onchange="if(confirm('Change role to ' + this.value + '?')) this.form.submit(); else this.value='<?php echo e($userRole); ?>';"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                            <?php $__currentLoopData = $availableRoles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($role); ?>" <?php echo e($userRole === $role ? 'selected' : ''); ?>><?php echo e(ucfirst($role)); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <span class="inline-block text-xs px-2 py-0.5 rounded border <?php echo e($roleClass($userRole)); ?>"><?php echo e($userRole); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <?php if($u->id !== auth()->id() && auth()->user()->hasAnyRole(['owner', 'admin']) && (auth()->user()->hasRole('owner') || ! $u->hasRole('owner'))): ?>
                                    <form method="POST" action="<?php echo e(route('team.destroy', $u)); ?>" onsubmit="return confirm('Remove <?php echo e($u->name); ?>? Their account will be deactivated.')" class="inline">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button class="text-rose-600 hover:underline text-xs">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-slate-500">No matches.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold">Invite</h2>
            </header>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('invite-users')): ?>
                <div class="p-4">
                    <form method="POST" action="<?php echo e(route('invitations.store')); ?>" class="space-y-3">
                        <?php echo csrf_field(); ?>
                        <input name="email" type="email" required placeholder="teammate@email.com"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <select name="role" class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button class="w-full px-3 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Send invite</button>
                    </form>
                </div>
            <?php else: ?>
                <p class="p-4 text-sm text-slate-500">You don't have permission to invite users.</p>
            <?php endif; ?>

            
            <?php if(auth()->user()->hasRole('owner')): ?>
                <details class="border-t border-slate-200 group">
                    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Transfer ownership
                        <span class="text-xs text-slate-400 group-open:hidden">▸</span>
                    </summary>
                    <form method="POST" action="" id="transferForm" class="px-4 pb-4 space-y-3"
                          onsubmit="if(!this.action){ alert('Pick a teammate first'); return false; } return confirm('Are you sure? You will become an admin.');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>

                        <p class="text-xs text-slate-600">
                            Promote another teammate to owner. You'll be demoted to admin — the workspace always has exactly one owner.
                        </p>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">New owner</label>
                            <select onchange="document.getElementById('transferForm').action = '/team/' + this.value + '/transfer-ownership'; document.getElementById('emailHint').textContent = this.options[this.selectedIndex].dataset.email || '';"
                                    class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                <option value="">— pick a teammate —</option>
                                <?php $__currentLoopData = $users->where('id', '!=', auth()->id())->where(fn ($u) => ! $u->hasRole('owner')); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $candidate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($candidate->id); ?>" data-email="<?php echo e($candidate->email); ?>"><?php echo e($candidate->name); ?> (<?php echo e($candidate->email); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Confirm by typing their email: <span id="emailHint" class="font-mono text-slate-500"></span>
                            </label>
                            <input name="confirmation" autocomplete="off"
                                   class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        </div>

                        <button class="w-full px-3 py-2 rounded-md bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">
                            Transfer ownership
                        </button>
                    </form>
                </details>
            <?php endif; ?>
        </section>
    </div>

    <?php if($pendingInvitations->isNotEmpty()): ?>
        <section class="mt-6 bg-white rounded-lg border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold">Pending invitations</h2>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-2">Email</th>
                        <th class="text-left px-5 py-2">Role</th>
                        <th class="text-left px-5 py-2">Invited by</th>
                        <th class="text-left px-5 py-2">Expires</th>
                        <th class="text-right px-5 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $pendingInvitations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invitation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-3"><?php echo e($invitation->email); ?></td>
                            <td class="px-5 py-3"><span class="inline-block text-xs px-2 py-0.5 rounded bg-slate-100"><?php echo e($invitation->role); ?></span></td>
                            <td class="px-5 py-3 text-slate-600"><?php echo e($invitation->invitedBy?->name ?? '—'); ?></td>
                            <td class="px-5 py-3 text-slate-600"><?php echo e($invitation->expires_at->diffForHumans()); ?></td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex justify-end gap-3 text-xs">
                                    <button onclick="navigator.clipboard.writeText('<?php echo e($invitation->acceptUrl()); ?>'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy link', 1500);" class="text-indigo-600 hover:underline">Copy link</button>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('invite-users')): ?>
                                        <form method="POST" action="<?php echo e(route('team.revoke-invitation', $invitation)); ?>" onsubmit="return confirm('Revoke this invitation?')">
                                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                            <button class="text-rose-600 hover:underline">Revoke</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\profitlens\resources\views/tenant/team/index.blade.php ENDPATH**/ ?>