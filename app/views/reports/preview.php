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

            <!-- Premium Content for Subscribers -->
            <?php if (!empty($hasActiveSubscription)): ?>
                <div class="card shadow-sm mt-5">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-3 text-success"><i class="fas fa-star me-2"></i>Premium Access Granted</h3>
                            <p class="mb-0">As a premium subscriber, you have instant access to detailed analysis and AI-powered insights!</p>
                        </div>
                        
                        <?php if (!empty($premiumContent)): ?>
                            <!-- Soul's Archetype -->
                            <?php if (!empty($premiumContent['souls_archetype'])): ?>
                                <div class="mb-4">
                                    <h5 class="text-primary"><i class="fas fa-soul me-2"></i>Soul's Archetype</h5>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($premiumContent['souls_archetype'])) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Planetary Influence -->
                            <?php if (!empty($premiumContent['planetary_influence'])): ?>
                                <div class="mb-4">
                                    <h5 class="text-primary"><i class="fas fa-globe me-2"></i>Planetary Influence</h5>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($premiumContent['planetary_influence'])) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Life Path Number -->
                            <?php if (!empty($premiumContent['life_path_number'])): ?>
                                <div class="mb-4">
                                    <h5 class="text-primary"><i class="fas fa-route me-2"></i>Life Path Number</h5>
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary fs-6 me-3"><?= htmlspecialchars($premiumContent['life_path_number']['number']) ?></span>
                                            <strong>Your Life Path Number</strong>
                                        </div>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($premiumContent['life_path_number']['interpretation'])) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Cosmic Summary -->
                            <?php if (!empty($premiumContent['cosmic_summary'])): ?>
                                <div class="mb-4">
                                    <h5 class="text-primary"><i class="fas fa-stars me-2"></i>Cosmic Summary</h5>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($premiumContent['cosmic_summary'])) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Historical Data from Report -->
                            <?php if (!empty($report['data'])): ?>
                                <?php $reportData = $report['data']; ?>
                                
                                <!-- Famous Births -->
                                <?php if (!empty($reportData['births'])): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary"><i class="fas fa-baby me-2"></i>Notable Births on Your Day</h5>
                                        <div class="bg-light p-3 rounded">
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach (array_slice($reportData['births'], 0, 5) as $birth): ?>
                                                    <li class="mb-2">
                                                        <strong><?= htmlspecialchars($birth['year']) ?>:</strong> 
                                                        <?= htmlspecialchars($birth['text']) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Notable Deaths -->
                                <?php if (!empty($reportData['deaths'])): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary"><i class="fas fa-skull-crossbones me-2"></i>Notable Deaths on Your Day</h5>
                                        <div class="bg-light p-3 rounded">
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach (array_slice($reportData['deaths'], 0, 5) as $death): ?>
                                                    <li class="mb-2">
                                                        <strong><?= htmlspecialchars($death['year']) ?>:</strong> 
                                                        <?= htmlspecialchars($death['text']) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Historical Events -->
                                <?php if (!empty($reportData['events'])): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary"><i class="fas fa-calendar-alt me-2"></i>Historical Events on Your Day</h5>
                                        <div class="bg-light p-3 rounded">
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach (array_slice($reportData['events'], 0, 5) as $event): ?>
                                                    <li class="mb-2">
                                                        <strong><?= htmlspecialchars($event['year']) ?>:</strong> 
                                                        <?= htmlspecialchars($event['text']) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <p><i class="fas fa-spinner fa-spin me-2"></i>Generating your personalized premium content...</p>
                                <small>This may take a moment as we create your unique cosmic insights.</small>
                            </div>
                        <?php endif; ?>
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