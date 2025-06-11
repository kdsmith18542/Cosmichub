<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">My Cosmic Reports</h2>
                    <p class="text-muted mb-0">View and manage your personalized cosmic reports</p>
                </div>
                <a href="/reports/create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Report
                </a>
            </div>

            <?php if (isset($_SESSION['_flash']['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= flash('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['_flash']['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= flash('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($reports)): ?>
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-file-alt fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-3">No Reports Yet</h4>
                    <p class="text-muted mb-4">You haven't created any cosmic reports yet. Start by creating your first report!</p>
                    <a href="/reports/create" class="btn btn-primary btn-lg">
                        <i class="fas fa-magic me-2"></i>Create Your First Report
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($reports as $report): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">
                                            <?= htmlspecialchars($report['title'] ?? 'Cosmic Report') ?>
                                        </h5>
                                        <span class="badge bg-primary">
                                            <?= format_date($report['birth_date']) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text text-muted small mb-3">
                                        Created on <?= format_datetime($report['created_at']) ?>
                                    </p>
                                    
                                    <?php if (!empty($report['summary'])): ?>
                                        <p class="card-text">
                                            <?= htmlspecialchars(substr($report['summary'], 0, 120)) ?>
                                            <?= strlen($report['summary']) > 120 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php if (!empty($report['has_events'])): ?>
                                            <span class="badge bg-info">Events</span>
                                        <?php endif; ?>
                                        <?php if (!empty($report['has_births'])): ?>
                                            <span class="badge bg-success">Births</span>
                                        <?php endif; ?>
                                        <?php if (!empty($report['has_deaths'])): ?>
                                            <span class="badge bg-warning">Deaths</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="btn-group" role="group">
                                            <a href="/reports/<?= $report['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="fas fa-download me-1"></i>Export
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="/reports/<?= $report['id'] ?>/export/pdf">
                                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="/reports/<?= $report['id'] ?>/export/png">
                                                        <i class="fas fa-image me-2"></i>PNG
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="confirmDelete(<?= $report['id'] ?>, '<?= htmlspecialchars($report['title'] ?? 'this report', ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination if needed -->
                <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                    <nav aria-label="Reports pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/reports?page=<?= $pagination['current_page'] - 1 ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="/reports?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/reports?page=<?= $pagination['current_page'] + 1 ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteReportTitle"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(reportId, reportTitle) {
    document.getElementById('deleteReportTitle').textContent = reportTitle;
    document.getElementById('deleteForm').action = '/reports/' + reportId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../app/views/layouts/footer.php'; ?>