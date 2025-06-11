<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Celebrity Almanac</h1>
        <?php if (is_admin()): ?>
            <a href="/celebrity-reports/create" class="btn btn-primary">Add Celebrity Report</a>
        <?php endif; ?>
    </div>

    <form action="/celebrity-reports/search" method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Search celebrities..." value="<?= htmlspecialchars($search_query ?? '') ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>

    <?php if (empty($celebrities)): ?>
        <div class="alert alert-info" role="alert">
            <?php if (isset($search_query)): ?>
                No celebrity reports found matching your search "<?= htmlspecialchars($search_query) ?>".
            <?php else: ?>
                No celebrity reports available yet.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($celebrities as $celebrity): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($celebrity->name) ?></h5>
                            <p class="card-text"><small class="text-muted">Born: <?= format_date($celebrity->birth_date) ?></small></p>
                            <a href="/celebrity-reports/<?= htmlspecialchars($celebrity->slug) ?>" class="btn btn-sm btn-outline-primary">View Report</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>