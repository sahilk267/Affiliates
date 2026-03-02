<?php ($title = 'Analytics'); ?>


<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Analytics & Reports</h1>
    <p class="mt-1 text-sm text-gray-500">View detailed analytics and performance metrics</p>
</div>

<!-- Date Range Filter -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="/admin/ui/analytics" class="flex items-end space-x-4">
        <div class="flex-1">
            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
            <input type="date" name="date_from" id="date_from" value="<?php echo e($dateFrom); ?>" 
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div class="flex-1">
            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
            <input type="date" name="date_to" id="date_to" value="<?php echo e($dateTo); ?>" 
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Analytics Charts Placeholder -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Clicks Over Time</h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
            <p class="text-sm text-gray-500">Chart visualization will be implemented here</p>
        </div>
        <div class="mt-4 space-y-2">
            <?php $__currentLoopData = $clicksOverTime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600"><?php echo e($item->date); ?></span>
                    <span class="font-medium text-gray-900"><?php echo e($item->count); ?> clicks</span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversions Over Time</h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
            <p class="text-sm text-gray-500">Chart visualization will be implemented here</p>
        </div>
        <div class="mt-4 space-y-2">
            <?php $__currentLoopData = $conversionsOverTime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600"><?php echo e($item->date); ?></span>
                    <span class="font-medium text-gray-900"><?php echo e($item->count); ?> conversions</span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<!-- Top Countries & Devices -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Top Countries</h3>
        </div>
        <div class="divide-y divide-gray-200">
            <?php $__empty_1 = true; $__currentLoopData = $topCountries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-6 py-4 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900"><?php echo e($country->country); ?></span>
                    <span class="text-sm text-gray-500"><?php echo e(number_format($country->count)); ?> clicks</span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-6 py-12 text-center text-sm text-gray-500">No data available</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Device Types</h3>
        </div>
        <div class="divide-y divide-gray-200">
            <?php $__empty_1 = true; $__currentLoopData = $deviceTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-6 py-4 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900"><?php echo e(ucfirst($device->device_type)); ?></span>
                    <span class="text-sm text-gray-500"><?php echo e(number_format($device->count)); ?> clicks</span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-6 py-12 text-center text-sm text-gray-500">No data available</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/u667128650/domains/zenithsoles.in/public_html/resources/views/admin/analytics.blade.php ENDPATH**/ ?>