<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Welcome Header -->
    <div class="px-4 py-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center">
                    <div class="flex-shrink-0 mb-4 mb-sm-0">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img class="rounded-circle" style="width: 4rem; height: 4rem;" src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="">
                        <?php else: ?>
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 4rem; height: 4rem; background-color: #e0e7ff;">
                                <span class="fs-4 fw-medium" style="color: #4f46e5;">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ms-0 ms-sm-4">
                        <h1 class="h2 fw-bold text-dark">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                        <p class="mt-1 text-muted">
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
    <div class="px-4 mb-4">
        <div class="row g-3">
            <!-- Credits Card -->
            <div class="col-12 col-sm-6 col-lg">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 rounded p-3" style="background-color: #6366f1;">
                                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <dl class="mb-0">
                                    <dt class="small fw-medium text-muted text-truncate">
                                        Available Credits
                                    </dt>
                                    <dd class="d-flex align-items-baseline mb-0">
                                        <div class="h4 fw-semibold text-dark mb-0">
                                            <?php echo number_format($user['credits']); ?>
                                        </div>
                                        <div class="ms-2 d-flex align-items-baseline small fw-semibold text-success">
                                            <a href="/payment/plans" class="text-primary text-decoration-none">
                                                Get More
                                            </a>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                
            <!-- Reports Card -->
            <div class="col-12 col-sm-6 col-lg">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 rounded p-3" style="background-color: #10b981;">
                                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <dl class="mb-0">
                                    <dt class="small fw-medium text-muted text-truncate">
                                        Reports Created
                                    </dt>
                                    <dd class="d-flex align-items-baseline mb-0">
                                        <div class="h4 fw-semibold text-dark mb-0">
                                            <?php echo number_format($stats['total_reports_created']); ?>
                                        </div>
                                        <div class="ms-2 d-flex align-items-baseline small fw-semibold">
                                            <a href="/reports" class="text-primary text-decoration-none">
                                                View All
                                            </a>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credits Earned Card -->
            <div class="col-12 col-sm-6 col-lg">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 rounded p-3" style="background-color: #eab308;">
                                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <dl class="mb-0">
                                    <dt class="small fw-medium text-muted text-truncate">
                                        Total Credits Earned
                                    </dt>
                                    <dd class="d-flex align-items-baseline mb-0">
                                        <div class="h4 fw-semibold text-dark mb-0">
                                            <?php echo number_format($stats['total_credits_earned']); ?>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Card -->
            <div class="col-12 col-sm-6 col-lg">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 rounded p-3" style="background-color: #8b5cf6;">
                                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <dl class="mb-0">
                                    <dt class="small fw-medium text-muted text-truncate">
                                        Membership Status
                                    </dt>
                                    <dd class="d-flex align-items-baseline mb-0">
                                        <div class="h4 fw-semibold text-dark mb-0">
                                            <?php echo ucfirst($user['subscription_status']); ?>
                                        </div>
                                        <div class="ms-2 d-flex align-items-baseline small fw-semibold">
                                            <a href="/subscription" class="text-primary text-decoration-none">
                                                <?php echo $user['has_subscription'] ? 'Manage' : 'Upgrade'; ?>
                                            </a>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rarity Score Card -->
            <?php if (isset($stats['rarityScore'])): ?>
            <div class="col-12 col-sm-6 col-lg">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 rounded p-3" style="background-color: <?php echo e($stats['rarityColor']); ?>">
                                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <dl class="mb-0">
                                    <dt class="small fw-medium text-muted text-truncate">
                                        Birthday Rarity Score
                                    </dt>
                                    <dd class="d-flex align-items-baseline mb-0">
                                        <div class="h4 fw-semibold text-dark mb-0">
                                            <?php echo e($stats['rarityScore']); ?>/100
                                        </div>
                                        <div class="ms-2 d-flex align-items-baseline small fw-semibold">
                                            <span class="badge rounded-pill" style="background-color: <?php echo e($stats['rarityColor']); ?>; color: white;">
                                                <?php echo e($stats['rarityDescription']); ?>
                                            </span>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-12 col-sm-6 col-lg">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 rounded p-3" style="background-color: #9ca3af;">
                                <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <dl class="mb-0">
                                    <dt class="small fw-medium text-muted text-truncate">
                                        Birthday Rarity Score
                                    </dt>
                                    <dd class="d-flex align-items-baseline mb-0">
                                        <div class="small text-muted">
                                            Add your birthdate to see how rare your birthday is!
                                        </div>
                                        <div class="ms-2 d-flex align-items-baseline small fw-semibold">
                                            <a href="/profile" class="text-primary text-decoration-none">
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
    <div class="container-fluid px-4">
        <div class="row g-4">
            <!-- Recent Transactions -->
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h3 class="h5 mb-1 fw-medium text-dark">
                            Recent Transactions
                        </h3>
                        <p class="mb-0 small text-muted">
                            Your recent credit transactions and purchases.
                        </p>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentTransactions)): ?>
                            <div class="p-4 text-center text-muted">
                                <p>No transactions found.</p>
                                <a href="/payment/plans" class="btn btn-primary btn-sm mt-2">
                                    Get Started
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <?php if ($transaction->type === 'credit'): ?>
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem; background-color: #dcfce7;">
                                                        <svg style="width: 1.25rem; height: 1.25rem; color: #16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                                        </svg>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem; background-color: #fecaca;">
                                                        <svg style="width: 1.25rem; height: 1.25rem; color: #dc2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ms-3 flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <p class="small fw-medium text-dark text-truncate mb-0">
                                                        <?php echo htmlspecialchars($transaction->description); ?>
                                                    </p>
                                                    <div class="ms-2 flex-shrink-0">
                                                        <p class="small fw-medium mb-0" style="color: <?php echo $transaction->type === 'credit' ? '#16a34a' : '#dc2626'; ?>;">
                                                            <?php echo e($transaction->type === 'credit' ? '+' : '-'); ?><?php echo e(number_format($transaction->amount)); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <p class="small text-muted mb-0">
                                                        <?php echo date('M j, Y', strtotime($transaction->created_at)); ?>
                                                    </p>
                                                    <p class="small text-muted mb-0" style="font-size: 0.75rem;">
                                                        <?php echo ucfirst($transaction->reference_type); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="card-footer text-end border-top">
                                <a href="/payment/history" class="small fw-medium text-primary text-decoration-none">
                                    View all transactions →
                                </a>
                            </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>

            <!-- Recent Reports -->
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h3 class="h5 mb-1 fw-medium text-dark">
                            Recent Reports
                        </h3>
                        <p class="mb-0 small text-muted">
                            Your most recent cosmic reports.
                        </p>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentReports)): ?>
                            <div class="p-4 text-center">
                                <svg class="mx-auto mb-3" style="width: 3rem; height: 3rem; color: #9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="small fw-medium text-dark mb-1">No reports</h3>
                                <p class="small text-muted mb-3">Get started by generating your first cosmic report.</p>
                                <a href="/reports/new" class="btn btn-primary btn-sm">
                                    <svg class="me-1" style="width: 1rem; height: 1rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    New Report
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentReports as $report): ?>
                                    <a href="/reports/<?php echo e($report->id); ?>" class="list-group-item list-group-item-action text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem; background-color: #e0e7ff;">
                                                    <svg style="width: 1.25rem; height: 1.25rem; color: #4f46e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <p class="small fw-medium text-dark text-truncate mb-0">
                                                    <?php echo htmlspecialchars($report->title); ?>
                                                </p>
                                                <p class="small text-muted mb-0">
                                                    <?php echo date('M j, Y', strtotime($report->created_at)); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="card-footer text-end border-top">
                                <a href="/reports" class="small fw-medium text-primary text-decoration-none">
                                    View all reports →
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header border-bottom">
                        <h3 class="h5 mb-0 fw-medium text-dark">
                            Quick Actions
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="/reports/new" class="list-group-item list-group-item-action text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 rounded p-2" style="background-color: #e0e7ff;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #4f46e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                    <div class="ms-3">
                                        <p class="small fw-medium text-dark mb-0">Create New Report</p>
                                        <p class="small text-muted mb-0">Generate a new cosmic report</p>
                                    </div>
                                </div>
                            </a>
                            <a href="/payment/plans" class="list-group-item list-group-item-action text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 rounded p-2" style="background-color: #dcfce7;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ms-3">
                                        <p class="small fw-medium text-dark mb-0">Buy More Credits</p>
                                        <p class="small text-muted mb-0">Get credits for more reports</p>
                                    </div>
                                </div>
                            </a>
                            <a href="/settings" class="list-group-item list-group-item-action text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 rounded p-2" style="background-color: #f3e8ff;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #8b5cf6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="ms-3">
                                        <p class="small fw-medium text-dark mb-0">Account Settings</p>
                                        <p class="small text-muted mb-0">Update your profile and preferences</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
