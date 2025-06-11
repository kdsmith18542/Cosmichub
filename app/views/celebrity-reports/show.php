<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h1 class="mb-0"><?= htmlspecialchars($celebrity->name) ?> - Cosmic Blueprint</h1>
        </div>
        <div class="card-body">
            <p class="lead"><strong>Birth Date:</strong> <?= format_date($celebrity->birth_date) ?></p>
            
            <hr>

            <?php $reportContent = json_decode($celebrity->report_content, true); ?>

            <?php if (!empty($reportContent)): ?>
                <div class="mt-4">
                    <h4>Historical Snapshot on <?= format_date($celebrity->birth_date, 'F jS') ?></h4>
                    
                    <?php if (!empty($reportContent['events'])): ?>
                        <div class="mt-3">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Significant Events</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($reportContent['events'] as $event): ?>
                                    <li class="list-group-item"><?= htmlspecialchars($event['year']) ?>: <?= htmlspecialchars($event['description']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($reportContent['births'])): ?>
                        <div class="mt-3">
                            <h5><i class="fas fa-birthday-cake me-2"></i>Notable Births</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($reportContent['births'] as $birth): ?>
                                    <li class="list-group-item"><?= htmlspecialchars($birth['year']) ?>: <?= htmlspecialchars($birth['description']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($reportContent['deaths'])): ?>
                        <div class="mt-3">
                            <h5><i class="fas fa-skull-crossbones me-2"></i>Notable Deaths</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($reportContent['deaths'] as $death): ?>
                                    <li class="list-group-item"><?= htmlspecialchars($death['year']) ?>: <?= htmlspecialchars($death['description']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Report content is not available for this celebrity.</p>
            <?php endif; ?>

            <div class="mt-4">
                <a href="/celebrity-reports" class="btn btn-secondary">Back to Almanac</a>
            </div>
        </div>
        <div class="card-footer text-muted">
            Report generated on <?= format_datetime($celebrity->created_at) ?>
        </div>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>