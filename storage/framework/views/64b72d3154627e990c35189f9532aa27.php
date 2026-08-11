<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'ProfitLens'); ?> · <?php echo e(config('app.name')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <style>html, body { font-family: 'Inter', system-ui, sans-serif; }</style>
    <?php echo $__env->make('layouts._profitlens-theme', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 <?php echo $__env->yieldContent('body-class'); ?>">
    <nav class="bg-white border-b border-slate-200 <?php echo $__env->yieldContent('nav-class'); ?>">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="<?php echo e(route('landing')); ?>" class="flex items-center gap-2 font-semibold text-lg">
                <span class="inline-block w-7 h-7 rounded bg-indigo-600"></span>
                ProfitLens
            </a>
            <div class="flex items-center gap-6 text-sm">
                <a href="<?php echo e(route('signup')); ?>" class="text-slate-600 hover:text-slate-900">Sign up</a>
                <a href="<?php echo e(route('billing')); ?>" class="text-slate-600 hover:text-slate-900">Billing</a>
                <a href="https://github.com/" class="hidden md:inline text-slate-600 hover:text-slate-900">Docs</a>
            </div>
        </div>
    </nav>

    <?php if(session('status')): ?>
        <div class="max-w-6xl mx-auto px-6 pt-6">
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                <?php echo e(session('status')); ?>

            </div>
        </div>
    <?php endif; ?>

    <main class="<?php echo $__env->yieldContent('main-class', 'max-w-6xl mx-auto px-6 py-10'); ?>">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer class="border-t border-slate-200 bg-white mt-16">
        <div class="max-w-6xl mx-auto px-6 py-6 text-xs text-slate-500 flex justify-between">
            <span>&copy; <?php echo e(date('Y')); ?> ProfitLens</span>
            <span>Multi-tenant SaaS scaffolded with Laravel + stancl/tenancy</span>
        </div>
    </footer>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\profitlens\resources\views/layouts/central.blade.php ENDPATH**/ ?>