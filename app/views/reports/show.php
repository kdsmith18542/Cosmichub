<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><?= htmlspecialchars($data['report']->title ?? 'Cosmic Report Details') ?></h3>
                    <a href="/reports" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-2"></i>Back to Reports</a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Birth Date:</strong> <?= format_date($data['report']->birth_date) ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1 text-muted">Generated on: <?= format_datetime($data['report']->created_at) ?></p>
                        </div>
                    </div>

                    <?php if (!empty($data['report']->summary)): ?>
                        <div class="alert alert-info mb-4" role="alert">
                            <h5 class="alert-heading"><i class="fas fa-lightbulb me-2"></i>Summary</h5>
                            <p class="mb-0"><?= htmlspecialchars($data['report']->summary) ?></p>
                        </div>
                    <?php endif; ?>

                    <h4 class="mt-4 mb-3">Detailed Analysis</h4>
                    <?php
                        $content = json_decode($data['report']->content, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $content = ['error' => 'Failed to decode report content.'];
                        }
                    ?>

                    <?php if (!empty($content['events'])): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Historical Events</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($content['events'] as $event): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($event['year']) ?>:</strong> <?= htmlspecialchars($event['text']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($content['births'])): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-baby me-2"></i>Notable Births</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($content['births'] as $birth): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($birth['year']) ?>:</strong> <?= htmlspecialchars($birth['text']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($content['deaths'])): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-skull-crossbones me-2"></i>Notable Deaths</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($content['deaths'] as $death): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($death['year']) ?>:</strong> <?= htmlspecialchars($death['text']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($content['events']) && empty($content['births']) && empty($content['deaths'])): ?>
                        <p class="text-muted">No detailed historical data available for this report.</p>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end mt-4">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/reports/<?= $data['report']->id ?>/export/pdf"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                                <li><a class="dropdown-item" href="/reports/<?= $data['report']->id ?>/export/png"><i class="fas fa-image me-2"></i>PNG</a></li>
                            </ul>
                        </div>
                        <a href="/reports/create" class="btn btn-primary ms-2"><i class="fas fa-redo me-2"></i>Generate New Report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>