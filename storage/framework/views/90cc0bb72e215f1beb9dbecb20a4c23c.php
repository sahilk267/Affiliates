<?php ($title = 'Dashboard'); ?>


<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
    <p class="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening with your affiliate system.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo e(number_format($stats['total_users'] ?? 0)); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Programs</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo e(number_format($stats['total_programs'] ?? 0)); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Clicks</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo e(number_format($stats['total_clicks'] ?? 0)); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Conversions</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo e(number_format($stats['total_conversions'] ?? 0)); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p class="text-sm font-medium text-gray-500 mb-1">Total Commission</p>
        <p class="text-xl font-semibold text-gray-900">₹<?php echo e(number_format($stats['total_commissions'] ?? 0, 2)); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p class="text-sm font-medium text-gray-500 mb-1">Pending Commission</p>
        <p class="text-xl font-semibold text-yellow-600">₹<?php echo e(number_format($stats['pending_commissions'] ?? 0, 2)); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p class="text-sm font-medium text-gray-500 mb-1">Paid Commission</p>
        <p class="text-xl font-semibold text-green-600">₹<?php echo e(number_format($stats['paid_commissions'] ?? 0, 2)); ?></p>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Recent Conversions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent Conversions</h2>
                <a href="/admin/ui/conversions" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
        </div>
        <div class="divide-y divide-gray-200">
            <?php $__empty_1 = true; $__currentLoopData = ($recentConversions ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-6 py-4 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?php echo e($c->user->name ?? 'N/A'); ?>

                            </p>
                            <p class="text-sm text-gray-500"><?php echo e($c->program->name ?? 'Program'); ?></p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <p class="text-sm font-semibold text-gray-900">₹<?php echo e(number_format($c->commission_amount, 2)); ?></p>
                            <p class="text-xs text-gray-500"><?php echo e($c->created_at?->format('M d, Y')); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No recent conversions</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Clicks -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent Clicks</h2>
                <a href="/admin/ui/clicks" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
        </div>
        <div class="divide-y divide-gray-200">
            <?php $__empty_1 = true; $__currentLoopData = ($recentClicks ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-6 py-4 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?php echo e($k->user->name ?? 'N/A'); ?>

                            </p>
                            <p class="text-sm text-gray-500"><?php echo e($k->program->name ?? 'Program'); ?> • <?php echo e($k->device_type ?? 'device'); ?></p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <p class="text-xs text-gray-500"><?php echo e($k->created_at?->format('M d, Y')); ?></p>
                            <p class="text-xs text-gray-400"><?php echo e($k->created_at?->format('H:i')); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No recent clicks</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Top Performers -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Programs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Top Performing Programs</h2>
        </div>
        <div class="divide-y divide-gray-200">
            <?php $__empty_1 = true; $__currentLoopData = ($topPrograms ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900"><?php echo e($program->name); ?></p>
                            <p class="text-xs text-gray-500"><?php echo e($program->conversions_count); ?> conversions • <?php echo e($program->clicks_count); ?> clicks</p>
                        </div>
                        <div class="ml-4 text-right">
                            <?php if($program->clicks_count > 0): ?>
                                <p class="text-sm font-semibold text-gray-900"><?php echo e(number_format(($program->conversions_count / $program->clicks_count) * 100, 2)); ?>%</p>
                                <p class="text-xs text-gray-500">Conversion Rate</p>
                            <?php else: ?>
                                <p class="text-sm text-gray-400">N/A</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-6 py-12 text-center text-sm text-gray-500">No programs yet</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Users -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Top Performing Users</h2>
        </div>
        <div class="divide-y divide-gray-200">
            <?php $__empty_1 = true; $__currentLoopData = ($topUsers ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900"><?php echo e($user->name); ?></p>
                            <p class="text-xs text-gray-500"><?php echo e($user->email); ?> • <?php echo e(ucfirst($user->role)); ?></p>
                        </div>
                        <div class="ml-4 text-right">
                            <p class="text-sm font-semibold text-gray-900"><?php echo e($user->conversions_count); ?></p>
                            <p class="text-xs text-gray-500">Conversions</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-6 py-12 text-center text-sm text-gray-500">No users yet</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/u667128650/domains/zenithsoles.in/public_html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>