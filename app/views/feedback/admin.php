<?php
/**
 * Admin Feedback Management View
 * 
 * Interface for managing user feedback during Phase 3 beta testing
 */

$title = $title ?? 'Feedback Management - CosmicHub Beta';
$feedbacks = $feedbacks ?? [];
$stats = $stats ?? [];
$filters = $filters ?? ['status' => 'all', 'type' => 'all', 'rating' => 'all'];
$pagination = $pagination ?? ['current' => 1, 'total' => 1, 'per_page' => 20];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 1rem;
        }
        
        .feedback-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }
        
        .feedback-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-in_progress {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-resolved {
            background: #d1edff;
            color: #0c5460;
        }
        
        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .type-bug {
            background: #ffebee;
            color: #c62828;
        }
        
        .type-feature {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .type-improvement {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .type-general {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .priority-high {
            color: #dc3545;
        }
        
        .priority-medium {
            color: #fd7e14;
        }
        
        .priority-low {
            color: #28a745;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .action-btn {
            border: none;
            border-radius: 8px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .btn-update:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(79, 172, 254, 0.3);
            color: white;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        
        .btn-view:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(67, 233, 123, 0.3);
            color: white;
        }
        
        .beta-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .feedback-content {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .feedback-content.expanded {
            max-height: none;
        }
        
        .expand-btn {
            background: none;
            border: none;
            color: #007bff;
            font-size: 0.8rem;
            padding: 0;
            text-decoration: underline;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1><i class="fas fa-comments"></i> Feedback Management</h1>
                    <span class="beta-badge">BETA TESTING</span>
                    <p class="mb-0 mt-2">Manage user feedback and improve CosmicHub experience</p>
                </div>
                <div class="col-auto">
                    <a href="/admin/dashboard" class="btn btn-light">
                        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-primary"><?= number_format($stats['total'] ?? 0) ?></h3>
                    <p class="mb-0 text-muted">Total Feedback</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-warning"><?= number_format($stats['pending'] ?? 0) ?></h3>
                    <p class="mb-0 text-muted">Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-success"><?= number_format($stats['resolved'] ?? 0) ?></h3>
                    <p class="mb-0 text-muted">Resolved</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-info"><?= number_format($stats['avg_rating'] ?? 0, 1) ?>/5</h3>
                    <p class="mb-0 text-muted">Avg Rating</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $filters['status'] == 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $filters['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= $filters['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="resolved" <?= $filters['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="closed" <?= $filters['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="all" <?= $filters['type'] == 'all' ? 'selected' : '' ?>>All Types</option>
                        <option value="bug" <?= $filters['type'] == 'bug' ? 'selected' : '' ?>>Bug Report</option>
                        <option value="feature" <?= $filters['type'] == 'feature' ? 'selected' : '' ?>>Feature Request</option>
                        <option value="improvement" <?= $filters['type'] == 'improvement' ? 'selected' : '' ?>>Improvement</option>
                        <option value="general" <?= $filters['type'] == 'general' ? 'selected' : '' ?>>General</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rating</label>
                    <select name="rating" class="form-select">
                        <option value="all" <?= $filters['rating'] == 'all' ? 'selected' : '' ?>>All Ratings</option>
                        <option value="5" <?= $filters['rating'] == '5' ? 'selected' : '' ?>>5 Stars</option>
                        <option value="4" <?= $filters['rating'] == '4' ? 'selected' : '' ?>>4 Stars</option>
                        <option value="3" <?= $filters['rating'] == '3' ? 'selected' : '' ?>>3 Stars</option>
                        <option value="2" <?= $filters['rating'] == '2' ? 'selected' : '' ?>>2 Stars</option>
                        <option value="1" <?= $filters['rating'] == '1' ? 'selected' : '' ?>>1 Star</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Feedback List -->
        <div class="row">
            <?php foreach ($feedbacks as $feedback): ?>
                <div class="col-12">
                    <div class="feedback-card">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="type-badge type-<?= htmlspecialchars($feedback['feedback_type']) ?>">
                                                <?= htmlspecialchars($feedback['feedback_type']) ?>
                                            </span>
                                            <span class="status-badge status-<?= htmlspecialchars($feedback['status']) ?> ms-2">
                                                <?= htmlspecialchars($feedback['status']) ?>
                                            </span>
                                            <?php if ($feedback['rating']): ?>
                                                <div class="rating-stars ms-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star<?= $i <= $feedback['rating'] ? '' : '-o' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h6 class="mb-2"><?= htmlspecialchars($feedback['subject'] ?? 'No Subject') ?></h6>
                                        
                                        <div class="feedback-content" id="content-<?= $feedback['id'] ?>">
                                            <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($feedback['message'])) ?></p>
                                        </div>
                                        
                                        <?php if (strlen($feedback['message']) > 200): ?>
                                            <button class="expand-btn" onclick="toggleContent(<?= $feedback['id'] ?>)" id="btn-<?= $feedback['id'] ?>">
                                                Show more
                                            </button>
                                        <?php endif; ?>
                                        
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-user"></i> 
                                                <?= $feedback['user_id'] ? 'User #' . $feedback['user_id'] : 'Anonymous' ?>
                                                <?php if ($feedback['email']): ?>
                                                    (<?= htmlspecialchars($feedback['email']) ?>)
                                                <?php endif; ?>
                                                | 
                                                <i class="fas fa-clock"></i> 
                                                <?= date('M j, Y H:i', strtotime($feedback['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="text-end">
                                    <div class="btn-group mb-2" role="group">
                                        <button class="btn action-btn btn-view btn-sm" 
                                                onclick="viewFeedback(<?= $feedback['id'] ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <div class="btn-group" role="group">
                                            <button class="btn action-btn btn-update btn-sm dropdown-toggle" 
                                                    data-bs-toggle="dropdown">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?= $feedback['id'] ?>, 'pending')">Mark Pending</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?= $feedback['id'] ?>, 'in_progress')">Mark In Progress</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?= $feedback['id'] ?>, 'resolved')">Mark Resolved</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?= $feedback['id'] ?>, 'closed')">Mark Closed</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <?php if ($feedback['admin_response']): ?>
                                        <div class="alert alert-info alert-sm p-2 mt-2">
                                            <small>
                                                <strong>Admin Response:</strong><br>
                                                <?= nl2br(htmlspecialchars($feedback['admin_response'])) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($feedbacks)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No feedback found</h5>
                        <p class="text-muted">No feedback matches your current filters.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total'] > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pagination['total']; $i++): ?>
                        <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $filters['status'] ?>&type=<?= $filters['type'] ?>&rating=<?= $filters['rating'] ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <!-- Feedback Detail Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Feedback Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="feedbackModalBody">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveResponse()">Save Response</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentFeedbackId = null;
        
        function toggleContent(id) {
            const content = document.getElementById(`content-${id}`);
            const btn = document.getElementById(`btn-${id}`);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                btn.textContent = 'Show more';
            } else {
                content.classList.add('expanded');
                btn.textContent = 'Show less';
            }
        }
        
        function viewFeedback(id) {
            currentFeedbackId = id;
            
            fetch(`/feedback/admin/details/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('feedbackModalBody').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
                    } else {
                        alert('Error loading feedback details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading feedback details');
                });
        }
        
        function updateStatus(id, status) {
            if (!confirm(`Are you sure you want to mark this feedback as ${status}?`)) {
                return;
            }
            
            fetch(`/feedback/admin/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: id,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }
        
        function saveResponse() {
            const responseText = document.getElementById('adminResponse').value;
            
            if (!responseText.trim()) {
                alert('Please enter a response');
                return;
            }
            
            fetch(`/feedback/admin/respond`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: currentFeedbackId,
                    response: responseText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
                    location.reload();
                } else {
                    alert('Error saving response: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving response');
            });
        }
        
        // Track page view
        if (typeof trackEvent === 'function') {
            trackEvent('page_view', {
                page: 'feedback_admin',
                filters: <?= json_encode($filters) ?>
            });
        }
    </script>
</body>
</html>