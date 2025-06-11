<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Welcome Header -->
    <div class="px-4 py-6 sm:px-0 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center">
                    <div class="flex-shrink-0 mb-4 sm:mb-0">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img class="h-16 w-16 rounded-full" src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="">
                        <?php else: ?>
                            <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-2xl font-medium text-indigo-600">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ml-0 sm:ml-6">
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                        <p class="mt-1 text-gray-600">
                            Member since <?php echo date('F Y', strtotime($user['joined_date'])); ?>
                            <?php if (!empty($user['last_login']) && $user['last_login'] !== 'Never'): ?>
                                • Last login: <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="px-4 mb-8">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Credits Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Available Credits
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php echo number_format($user['credits']); ?>
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                        <a href="/payment/plans" class="text-indigo-600 hover:text-indigo-500">
                                            Get More
                                        </a>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
                
            <!-- Reports Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Reports Created
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php echo number_format($stats['total_reports_created']); ?>
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <a href="/reports" class="text-indigo-600 hover:text-indigo-500">
                                            View All
                                        </a>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credits Earned Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Total Credits Earned
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php echo number_format($stats['total_credits_earned']); ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Membership Status
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php echo ucfirst($user['subscription_status']); ?>
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <a href="/subscription" class="text-indigo-600 hover:text-indigo-500">
                                            <?php echo $user['has_subscription'] ? 'Manage' : 'Upgrade'; ?>
                                        </a>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rarity Score Card -->
            <?php if (isset($rarityScore)): ?>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3" style="background-color: <?php echo e($rarityColor); ?>">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Birthday Rarity Score
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php echo e($rarityScore); ?>/100
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <span class="px-2 py-1 rounded-full text-xs" style="background-color: <?php echo e($rarityColor); ?>; color: white;">
                                            <?php echo e($rarityDescription); ?>
                                        </span>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gray-400 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Birthday Rarity Score
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-sm text-gray-500">
                                        Add your birthdate to see how rare your birthday is!
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <a href="/profile" class="text-indigo-600 hover:text-indigo-500">
                                            Add Now
                                        </a>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
                
    <!-- Main Content -->
    <div class="px-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Transactions -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Recent Transactions
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Your recent credit transactions and purchases.
                    </p>
                </div>
                <div class="bg-white overflow-hidden">
                    <?php if (empty($recentTransactions)): ?>
                        <div class="px-6 py-4 text-center text-gray-500">
                            <p>No transactions found.</p>
                            <a href="/payment/plans" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Get Started
                            </a>
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <li class="px-6 py-4 hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <?php if ($transaction->type === 'credit'): ?>
                                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                                    </svg>
                                                </div>
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex justify-between">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    <?php echo htmlspecialchars($transaction->description); ?>
                                                </p>
                                                <div class="ml-2 flex-shrink-0 flex">
                                                    <p class="text-sm font-medium <?php echo $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600'; ?>">
                                                        <?php echo e($transaction->type === 'credit' ? '+' : '-'); ?><?php echo e(number_format($transaction->amount)); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex justify-between mt-1">
                                                <p class="text-sm text-gray-500">
                                                    <?php echo date('M j, Y', strtotime($transaction->created_at)); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo ucfirst($transaction->reference_type); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="px-6 py-3 bg-gray-50 text-right border-t border-gray-200">
                            <a href="/payment/history" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View all transactions →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div>
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Recent Reports
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Your most recent cosmic reports.
                    </p>
                </div>
                <div class="bg-white overflow-hidden">
                    <?php if (empty($recentReports)): ?>
                        <div class="px-6 py-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No reports</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by generating your first cosmic report.</p>
                            <div class="mt-6">
                                <a href="/reports/new" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    New Report
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($recentReports as $report): ?>
                                <li class="px-6 py-4 hover:bg-gray-50">
                                    <a href="/reports/<?php echo e($report->id); ?>" class="block">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    <?php echo htmlspecialchars($report->title); ?>
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo date('M j, Y', strtotime($report->created_at)); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="px-6 py-3 bg-gray-50 text-right border-t border-gray-200">
                            <a href="/reports" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View all reports →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Quick Actions
                    </h3>
                </div>
                <div class="bg-white">
                    <ul class="divide-y divide-gray-200">
                        <li>
                            <a href="/reports/new" class="block px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-indigo-100 p-2 rounded-md">
                                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">Create New Report</p>
                                        <p class="text-sm text-gray-500">Generate a new cosmic report</p>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/payment/plans" class="block px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-100 p-2 rounded-md">
                                        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">Buy More Credits</p>
                                        <p class="text-sm text-gray-500">Get credits for more reports</p>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/settings" class="block px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-purple-100 p-2 rounded-md">
                                        <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">Account Settings</p>
                                        <p class="text-sm text-gray-500">Update your profile and preferences</p>
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
