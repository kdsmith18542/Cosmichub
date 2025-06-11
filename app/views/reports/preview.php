<?php require_once '../app/views/layouts/header.php'; ?>

<?php
$report = session('temp_report');
if (!$report) {
    redirect('/reports/create');
    exit;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><?= htmlspecialchars($report['title']) ?></h2>
                    <p class="text-muted mb-0">Generated on <?= format_datetime($report['created_at']) ?></p>
                </div>
                <div class="btn-group">
                    <a href="/reports/create" class="btn btn-outline-secondary">
                        <i class="fas fa-plus me-2"></i>Create Another
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['_flash']['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= flash('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Cosmic Snapshot (Free Section) -->
            <div class="card shadow-sm" id="reportContent">
                <div class="card-body p-4">
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bold text-primary mb-3">Cosmic Snapshot</h1>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="bg-light rounded p-3">
                                    <h5 class="mb-2">Birth Date</h5>
                                    <p class="fs-4 fw-bold text-dark mb-0"><?= $report['formatted_birth_date'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($report['data']['events'])): ?>
                        <div class="mb-5">
                            <h3 class="border-bottom pb-2 mb-4">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                Historical Events on Your Birth Date
                            </h3>
                            <div class="row">
                                <?php foreach ($report['data']['events'] as $index => $event): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border-start border-primary border-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <span class="badge bg-primary"><?= $event['year'] ?? 'Unknown Year' ?></span>
                                                    <small class="text-muted">#<?= $index + 1 ?></small>
                                                </div>
                                                <p class="card-text"><?= htmlspecialchars($event['text'] ?? 'No description available') ?></p>
                                                <?php if (!empty($event['pages'])): ?>
                                                    <div class="mt-2">
                                                        <?php foreach (array_slice($event['pages'], 0, 2) as $page): ?>
                                                            <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($page['displaytitle'] ?? $page['title'] ?? '') ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($report['data']['events'])): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Historical Data Found</h4>
                            <p class="text-muted">We couldn't find any historical events for your birth date. This might be due to limited data availability or API issues.</p>
                            <a href="/reports/create" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i>Try Again
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Unlock Wall for Premium Content -->
            <?php if (!empty($hasActiveSubscription)): ?>
                <div class="card shadow-sm mt-5">
                    <div class="card-body text-center">
                        <h3 class="mb-3 text-success"><i class="fas fa-star me-2"></i>Premium Access Granted</h3>
                        <p class="mb-4">As a premium subscriber, you have instant access to detailed analysis, famous births, notable deaths, and AI-powered insights!</p>
                        <!-- TODO: Insert premium content rendering here -->
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mt-5">
                    <div class="card-body text-center">
                        <h3 class="mb-3"><i class="fas fa-lock text-warning me-2"></i>Unlock Your Full Cosmic Report</h3>
                        <p class="mb-4">Share your unique referral link with 3 friends or upgrade to premium to unlock detailed analysis, famous births, notable deaths, and AI-powered insights!</p>
                        <a href="/upgrade" class="btn btn-primary me-2"><i class="fas fa-star me-2"></i>Upgrade to Premium</a>
                        <a href="/referrals" class="btn btn-outline-secondary"><i class="fas fa-share-alt me-2"></i>Share to Unlock</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="/reports" class="btn btn-outline-primary me-2">
                    <i class="fas fa-list me-2"></i>View All Reports
                </a>
                <a href="/reports/create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Another Report
                </a>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .alert, .border-top:last-child {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .container {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<script>
setTimeout(() => {
    fetch('/reports/clear-temp', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    });
}, 30000);
</script>

<?php require_once '../app/views/layouts/footer.php'; ?>