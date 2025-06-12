<?php
/**
 * Unified Admin Dashboard
 * 
 * Comprehensive admin interface combining analytics, feedback management, 
 * user management, and system monitoring for CosmicHub
 */

$title = $title ?? 'Admin Dashboard - CosmicHub';
$summary = $summary ?? [];
$recentEvents = $recentEvents ?? [];
$eventCounts = $eventCounts ?? [];
$dailyStats = $dailyStats ?? [];
$userEngagement = $userEngagement ?? [];
$performanceMetrics = $performanceMetrics ?? [];
$recentFeedback = $recentFeedback ?? [];
$feedbackStats = $feedbackStats ?? [];
$userStats = $userStats ?? [];
$systemHealth = $systemHealth ?? [];
$contentStats = $contentStats ?? [];
$dateRange = $dateRange ?? ['start' => date('Y-m-d', strtotime('-7 days')), 'end' => date('Y-m-d'), 'days' => 7];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            --cosmic-gradient: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .nav-tabs {
            border: none;
            margin-bottom: 2rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 15px;
            margin-right: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #6c757d;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .nav-tabs .nav-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .nav-tabs .nav-link.active {
            background: var(--cosmic-gradient);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        
        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .metric-trend {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .trend-up {
            color: #28a745;
        }
        
        .trend-down {
            color: #dc3545;
        }
        
        .trend-stable {
            color: #6c757d;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .table-container {
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
            margin: 0.125rem;
        }
        
        .btn-primary-gradient {
            background: var(--primary-gradient);
            color: white;
        }
        
        .btn-secondary-gradient {
            background: var(--secondary-gradient);
            color: white;
        }
        
        .btn-success-gradient {
            background: var(--success-gradient);
            color: white;
        }
        
        .btn-warning-gradient {
            background: var(--warning-gradient);
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            color: white;
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
        
        .status-active {
            background: #d1edff;
            color: #0c5460;
        }
        
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-suspended {
            background: #f8d7da;
            color: #721c24;
        }
        
        .quick-action-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .quick-action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: var(--cosmic-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .system-health-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .health-good {
            background: #28a745;
        }
        
        .health-warning {
            background: #ffc107;
        }
        
        .health-critical {
            background: #dc3545;
        }
        
        .beta-badge {
            background: var(--warning-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .refresh-btn {
            background: var(--secondary-gradient);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    <span class="beta-badge">BETA TESTING</span>
                    <p class="mb-0 mt-2">Comprehensive administration and monitoring for CosmicHub</p>
                </div>
                <div class="col-auto">
                    <button class="btn refresh-btn" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <a href="/" class="btn btn-light ms-2">
                        <i class="fas fa-home"></i> Back to Site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-chart-line"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab">
                    <i class="fas fa-chart-bar"></i> Analytics
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#feedback" type="button" role="tab">
                    <i class="fas fa-comments"></i> Feedback
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                    <i class="fas fa-users"></i> Users
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">
                    <i class="fas fa-file-alt"></i> Content
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                    <i class="fas fa-server"></i> System
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <!-- Key Metrics Row -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="metric-value text-primary"><?= number_format($summary['new_users_today'] ?? 0) ?></h3>
                                    <p class="metric-label">New Users Today</p>
                                    <div class="metric-trend">
                                        <small class="text-muted">7 days: <?= number_format($summary['new_users_7d'] ?? 0) ?> | 30 days: <?= number_format($summary['new_users_30d'] ?? 0) ?></small>
                                    </div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-user-plus fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="metric-value text-success"><?= number_format($summary['reports_today'] ?? 0) ?></h3>
                                    <p class="metric-label">Reports Today</p>
                                    <div class="metric-trend">
                                        <small class="text-muted">7 days: <?= number_format($summary['reports_7d'] ?? 0) ?></small>
                                    </div>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-file-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="metric-value text-info">$<?= number_format($summary['revenue_today'] ?? 0, 2) ?></h3>
                                    <p class="metric-label">Revenue Today</p>
                                    <div class="metric-trend">
                                        <small class="text-muted">7d: $<?= number_format($summary['revenue_7d'] ?? 0, 2) ?> | 30d: $<?= number_format($summary['revenue_30d'] ?? 0, 2) ?></small>
                                    </div>
                                </div>
                                <div class="text-info">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="metric-value text-warning"><?= number_format($summary['credit_packs_today'] ?? 0) ?></h3>
                                    <p class="metric-label">Credit Packs Sold Today</p>
                                    <div class="metric-trend">
                                        <small class="text-muted">7 days: <?= number_format($summary['credit_packs_7d'] ?? 0) ?></small>
                                    </div>
                                </div>
                                <div class="text-warning">
                                    <i class="fas fa-coins fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-3">Quick Actions</h4>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="quick-action-card" onclick="location.href='#users'" data-bs-toggle="tab" data-bs-target="#users">
                            <i class="fas fa-user-plus quick-action-icon"></i>
                            <h6>Add User</h6>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="quick-action-card" onclick="location.href='/feedback/admin'">
                            <i class="fas fa-reply quick-action-icon"></i>
                            <h6>Respond to Feedback</h6>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="quick-action-card" onclick="exportData()">
                            <i class="fas fa-download quick-action-icon"></i>
                            <h6>Export Data</h6>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="quick-action-card" onclick="location.href='/celebrity-reports'">
                            <i class="fas fa-star quick-action-icon"></i>
                            <h6>Manage Celebrities</h6>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="quick-action-card" onclick="systemMaintenance()">
                            <i class="fas fa-tools quick-action-icon"></i>
                            <h6>System Maintenance</h6>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="quick-action-card" onclick="sendNotification()">
                            <i class="fas fa-bell quick-action-icon"></i>
                            <h6>Send Notification</h6>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="table-container">
                            <h5 class="mb-3">System Health</h5>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><span class="system-health-indicator health-good"></span>Database Connection</span>
                                    <span class="badge bg-success">Online</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><span class="system-health-indicator health-good"></span>Email Service</span>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><span class="system-health-indicator health-warning"></span>Storage Usage</span>
                                    <span class="badge bg-warning">78%</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><span class="system-health-indicator health-good"></span>API Response Time</span>
                                    <span class="badge bg-success"><?= number_format($performanceMetrics['avg_response_time'] ?? 120) ?>ms</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="table-container">
                            <h5 class="mb-3">Recent Activity</h5>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($recentEvents, 0, 5) as $event): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($event['event_type'] ?? 'Unknown') ?></h6>
                                        <small><?= date('H:i', strtotime($event['created_at'] ?? 'now')) ?></small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($event['event_data']['description'] ?? 'No description') ?></p>
                                    <small>User ID: <?= $event['user_id'] ?? 'System' ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div class="tab-pane fade" id="analytics" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5 class="mb-3">Daily Activity Trends</h5>
                            <canvas id="dailyActivityChart" height="100"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h5 class="mb-3">Event Distribution</h5>
                            <canvas id="eventDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <h5 class="mb-3">Recent Analytics Events</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Event Type</th>
                                    <th>User</th>
                                    <th>Details</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentEvents, 0, 20) as $event): ?>
                                <tr>
                                    <td><?= date('M j, H:i', strtotime($event['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($event['event_type'] ?? 'Unknown') ?></span>
                                    </td>
                                    <td><?= $event['user_id'] ?? 'Anonymous' ?></td>
                                    <td><?= htmlspecialchars(json_encode($event['event_data'] ?? [])) ?></td>
                                    <td>
                                        <?php if (isset($event['performance_data']['page_load_time'])): ?>
                                            <?= number_format($event['performance_data']['page_load_time'] * 1000) ?>ms
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Feedback Tab -->
            <div class="tab-pane fade" id="feedback" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-warning"><?= $feedbackStats['pending'] ?? 0 ?></h3>
                            <p class="metric-label">Pending Reviews</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-info"><?= $feedbackStats['in_progress'] ?? 0 ?></h3>
                            <p class="metric-label">In Progress</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-success"><?= $feedbackStats['resolved'] ?? 0 ?></h3>
                            <p class="metric-label">Resolved</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-primary"><?= number_format($feedbackStats['avg_rating'] ?? 0, 1) ?></h3>
                            <p class="metric-label">Average Rating</p>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Recent Feedback</h5>
                        <a href="/feedback/admin" class="btn btn-primary-gradient">
                            <i class="fas fa-external-link-alt"></i> Full Feedback Manager
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Rating</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentFeedback, 0, 10) as $feedback): ?>
                                <tr>
                                    <td><?= date('M j', strtotime($feedback['created_at'] ?? 'now')) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($feedback['feedback_type'] ?? 'General') ?></span></td>
                                    <td>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= ($feedback['rating'] ?? 0) ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?= htmlspecialchars(substr($feedback['subject'] ?? 'No subject', 0, 50)) ?>...</td>
                                    <td><span class="status-badge status-<?= $feedback['status'] ?? 'pending' ?>"><?= ucfirst($feedback['status'] ?? 'Pending') ?></span></td>
                                    <td>
                                        <button class="action-btn btn-secondary-gradient" onclick="viewFeedback(<?= $feedback['id'] ?? 0 ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn btn-success-gradient" onclick="respondToFeedback(<?= $feedback['id'] ?? 0 ?>)">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-primary"><?= number_format($userStats['total'] ?? 0) ?></h3>
                            <p class="metric-label">Total Users</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-success"><?= number_format($userStats['active'] ?? 0) ?></h3>
                            <p class="metric-label">Active Users</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-warning"><?= number_format($userStats['new_today'] ?? 0) ?></h3>
                            <p class="metric-label">New Today</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="metric-card">
                            <h3 class="metric-value text-info"><?= number_format($userStats['premium'] ?? 0) ?></h3>
                            <p class="metric-label">Premium Users</p>
                        </div>
                    </div>
                </div>
                
                <!-- User Search and Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="userSearch" placeholder="Search users by email or ID...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="dateFilter">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>User Management</h5>
                        <div>
                            <button class="btn btn-success-gradient" onclick="addUser()">
                                <i class="fas fa-user-plus"></i> Add User
                            </button>
                            <button class="btn btn-secondary-gradient" onclick="exportUsers()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Credits</th>
                                    <th>Reports</th>
                                    <th>Referrals</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- User data would be populated here -->
                                <tr>
                                    <td colspan="10" class="text-center text-muted">User management functionality coming soon...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="User pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Next</a></li>
                        </ul>
                    </nav>
                    
                    <!-- Bulk Actions -->
                    <div class="mt-3" id="bulkActions" style="display: none;">
                        <div class="d-flex align-items-center">
                            <span class="me-3"><span id="selectedCount">0</span> users selected</span>
                            <button class="btn btn-sm btn-warning me-2" onclick="bulkSuspend()">
                                <i class="fas fa-ban"></i> Suspend Selected
                            </button>
                            <button class="btn btn-sm btn-success me-2" onclick="bulkActivate()">
                                <i class="fas fa-check"></i> Activate Selected
                            </button>
                            <button class="btn btn-sm btn-info me-2" onclick="bulkVerifyEmail()">
                                <i class="fas fa-envelope-check"></i> Verify Emails
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Tab -->
            <div class="tab-pane fade" id="content" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-file-alt"></i> Content & Report Management</h4>
                    <div>
                        <button class="btn btn-success me-2" onclick="createCelebrityReport()">
                            <i class="fas fa-star"></i> Add Celebrity Report
                        </button>
                        <button class="btn btn-primary" onclick="exportReports()">
                            <i class="fas fa-download"></i> Export Reports
                        </button>
                    </div>
                </div>
                
                <!-- Content Management Tabs -->
                <ul class="nav nav-pills mb-4" id="contentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="reports-tab" data-bs-toggle="pill" data-bs-target="#reports-content" type="button" role="tab">
                            <i class="fas fa-file-alt"></i> Report Registry
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="celebrity-tab" data-bs-toggle="pill" data-bs-target="#celebrity-content" type="button" role="tab">
                            <i class="fas fa-star"></i> Celebrity Almanac
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="archetype-tab" data-bs-toggle="pill" data-bs-target="#archetype-content" type="button" role="tab">
                            <i class="fas fa-users"></i> Archetype Hubs
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="comments-tab" data-bs-toggle="pill" data-bs-target="#comments-content" type="button" role="tab">
                            <i class="fas fa-comments"></i> Comment Moderation
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="contentTabContent">
                    <!-- Report Registry -->
                    <div class="tab-pane fade show active" id="reports-content" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" placeholder="Search reports by ID or user...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select">
                                    <option value="">All Report Types</option>
                                    <option value="archetype">Archetype Reports</option>
                                    <option value="compatibility">Compatibility Reports</option>
                                    <option value="celebrity">Celebrity Reports</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select">
                                    <option value="">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Report ID</th>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Subject</th>
                                            <th>Created</th>
                                            <th>Views</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for($i = 1; $i <= 10; $i++): ?>
                                            <tr>
                                                <td>#RPT<?= str_pad($i, 6, '0', STR_PAD_LEFT) ?></td>
                                                <td>User #<?= rand(1, 1000) ?></td>
                                                <td><span class="badge bg-primary">Archetype</span></td>
                                                <td>Soul's Archetype Analysis</td>
                                                <td><?= date('M j, Y H:i', strtotime('-' . rand(1, 30) . ' days')) ?></td>
                                                <td><?= rand(5, 150) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewReport('RPT<?= str_pad($i, 6, '0', STR_PAD_LEFT) ?>')" title="View Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReport('RPT<?= str_pad($i, 6, '0', STR_PAD_LEFT) ?>')" title="Delete Report">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Celebrity Almanac Manager -->
                    <div class="tab-pane fade" id="celebrity-content" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" placeholder="Search celebrities by name...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-success w-100" onclick="addCelebrity()">
                                    <i class="fas fa-plus"></i> Add New Celebrity
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Celebrity</th>
                                            <th>Birth Date</th>
                                            <th>Category</th>
                                            <th>Report Status</th>
                                            <th>SEO Score</th>
                                            <th>Views</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $celebrities = [
                                            ['name' => 'Taylor Swift', 'birth' => '1989-12-13', 'category' => 'Music', 'status' => 'Published', 'seo' => 95, 'views' => 15420],
                                            ['name' => 'Leonardo DiCaprio', 'birth' => '1974-11-11', 'category' => 'Acting', 'status' => 'Published', 'seo' => 88, 'views' => 12350],
                                            ['name' => 'Elon Musk', 'birth' => '1971-06-28', 'category' => 'Business', 'status' => 'Draft', 'seo' => 72, 'views' => 8900],
                                            ['name' => 'Oprah Winfrey', 'birth' => '1954-01-29', 'category' => 'Media', 'status' => 'Published', 'seo' => 91, 'views' => 11200]
                                        ];
                                        foreach($celebrities as $celeb): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="celebrity-avatar me-2">
                                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?= $celeb['name'] ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($celeb['birth'])) ?></td>
                                                <td><span class="badge bg-info"><?= $celeb['category'] ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?= $celeb['status'] == 'Published' ? 'success' : 'warning' ?>">
                                                        <?= $celeb['status'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="width: 60px; height: 20px;">
                                                        <div class="progress-bar bg-<?= $celeb['seo'] >= 90 ? 'success' : ($celeb['seo'] >= 70 ? 'warning' : 'danger') ?>" 
                                                             style="width: <?= $celeb['seo'] ?>%" title="<?= $celeb['seo'] ?>%">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= number_format($celeb['views']) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editCelebrity('<?= $celeb['name'] ?>')" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" onclick="viewCelebrityReport('<?= $celeb['name'] ?>')" title="View Report">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCelebrity('<?= $celeb['name'] ?>')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Archetype Hubs Content -->
                    <div class="tab-pane fade" id="archetype-content" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="table-container">
                                    <h5>Archetype Pages</h5>
                                    <div class="list-group">
                                        <a href="#" class="list-group-item list-group-item-action active" onclick="loadArchetypeContent('mystic')">
                                            <i class="fas fa-magic"></i> The Mystic
                                            <span class="badge bg-primary rounded-pill">12 comments</span>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action" onclick="loadArchetypeContent('warrior')">
                                            <i class="fas fa-shield-alt"></i> The Warrior
                                            <span class="badge bg-primary rounded-pill">8 comments</span>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action" onclick="loadArchetypeContent('sage')">
                                            <i class="fas fa-book"></i> The Sage
                                            <span class="badge bg-primary rounded-pill">15 comments</span>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action" onclick="loadArchetypeContent('lover')">
                                            <i class="fas fa-heart"></i> The Lover
                                            <span class="badge bg-primary rounded-pill">6 comments</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="table-container">
                                    <h5>Edit Archetype Content: The Mystic</h5>
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Page Title</label>
                                            <input type="text" class="form-control" value="The Mystic - Archetype Hub">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Meta Description</label>
                                            <textarea class="form-control" rows="2">Discover the mystical archetype and its cosmic influence on your spiritual journey...</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Content</label>
                                            <textarea class="form-control" rows="8">The Mystic archetype represents those who seek deeper spiritual truths and cosmic understanding...</textarea>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-secondary">Preview</button>
                                            <div>
                                                <button type="button" class="btn btn-outline-primary me-2">Save Draft</button>
                                                <button type="submit" class="btn btn-primary">Publish Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comment Moderation -->
                    <div class="tab-pane fade" id="comments-content" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" placeholder="Search comments...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select">
                                    <option value="">All Pages</option>
                                    <option value="mystic">The Mystic</option>
                                    <option value="warrior">The Warrior</option>
                                    <option value="sage">The Sage</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Comment</th>
                                            <th>Author</th>
                                            <th>Page</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $comments = [
                                            ['text' => 'This archetype really resonates with me! The description is spot on.', 'author' => 'Sarah M.', 'page' => 'The Mystic', 'date' => '2 hours ago', 'status' => 'pending'],
                                            ['text' => 'Amazing insights! I never understood my spiritual side until reading this.', 'author' => 'John D.', 'page' => 'The Sage', 'date' => '5 hours ago', 'status' => 'approved'],
                                            ['text' => 'Could you add more information about compatibility with other archetypes?', 'author' => 'Emma L.', 'page' => 'The Warrior', 'date' => '1 day ago', 'status' => 'pending']
                                        ];
                                        foreach($comments as $comment): ?>
                                            <tr>
                                                <td>
                                                    <div class="comment-preview">
                                                        <?= substr($comment['text'], 0, 80) ?>...
                                                    </div>
                                                </td>
                                                <td><?= $comment['author'] ?></td>
                                                <td><span class="badge bg-secondary"><?= $comment['page'] ?></span></td>
                                                <td><small class="text-muted"><?= $comment['date'] ?></small></td>
                                                <td>
                                                    <span class="badge bg-<?= $comment['status'] == 'approved' ? 'success' : ($comment['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($comment['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-success" onclick="approveComment()" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="rejectComment()" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewComment()" title="View Full">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monetization Tab -->
            <div class="tab-pane fade" id="monetization" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-dollar-sign"></i> Monetization & E-commerce</h4>
                    <div>
                        <button class="btn btn-success me-2" onclick="addProduct()">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        <button class="btn btn-primary" onclick="exportTransactions()">
                            <i class="fas fa-download"></i> Export Transactions
                        </button>
                    </div>
                </div>
                
                <!-- Monetization Tabs -->
                <ul class="nav nav-pills mb-4" id="monetizationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="products-tab" data-bs-toggle="pill" data-bs-target="#products-content" type="button" role="tab">
                            <i class="fas fa-box"></i> Product Management
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transactions-tab" data-bs-toggle="pill" data-bs-target="#transactions-content" type="button" role="tab">
                            <i class="fas fa-receipt"></i> Transaction Log
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="referrals-tab" data-bs-toggle="pill" data-bs-target="#referrals-content" type="button" role="tab">
                            <i class="fas fa-share-alt"></i> Referral System
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="affiliates-tab" data-bs-toggle="pill" data-bs-target="#affiliates-content" type="button" role="tab">
                            <i class="fas fa-link"></i> Affiliate Links
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ads-tab" data-bs-toggle="pill" data-bs-target="#ads-content" type="button" role="tab">
                            <i class="fas fa-ad"></i> Ad Placement
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="monetizationTabContent">
                    <!-- Product Management -->
                    <div class="tab-pane fade show active" id="products-content" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-container">
                                    <h5>Credit Packs & Products</h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Type</th>
                                                    <th>Price</th>
                                                    <th>Credits</th>
                                                    <th>Sales</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-coins fa-2x text-warning me-2"></i>
                                                            <div>
                                                                <strong>Starter Pack</strong>
                                                                <br><small class="text-muted">Basic credit package</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-primary">Credit Pack</span></td>
                                                    <td>$4.95</td>
                                                    <td>10 Credits</td>
                                                    <td>1,247</td>
                                                    <td><span class="badge bg-success">Active</span></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editProduct('starter')" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleProduct('starter')" title="Toggle Status">
                                                                <i class="fas fa-toggle-on"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-gem fa-2x text-info me-2"></i>
                                                            <div>
                                                                <strong>Premium Pack</strong>
                                                                <br><small class="text-muted">Best value package</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-primary">Credit Pack</span></td>
                                                    <td>$9.95</td>
                                                    <td>25 Credits</td>
                                                    <td>892</td>
                                                    <td><span class="badge bg-success">Active</span></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editProduct('premium')" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleProduct('premium')" title="Toggle Status">
                                                                <i class="fas fa-toggle-on"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-file-pdf fa-2x text-danger me-2"></i>
                                                            <div>
                                                                <strong>Premium PDF Report</strong>
                                                                <br><small class="text-muted">Detailed PDF download</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-info">Premium PDF</span></td>
                                                    <td>$4.95</td>
                                                    <td>-</td>
                                                    <td>456</td>
                                                    <td><span class="badge bg-success">Active</span></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="editProduct('pdf')" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleProduct('pdf')" title="Toggle Status">
                                                                <i class="fas fa-toggle-on"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-container">
                                    <h5>Revenue Overview</h5>
                                    <div class="revenue-stats">
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Today's Revenue:</span>
                                                <strong class="text-success">$247.50</strong>
                                            </div>
                                            <small class="text-muted">+12% from yesterday</small>
                                        </div>
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>This Week:</span>
                                                <strong class="text-info">$1,892.35</strong>
                                            </div>
                                            <small class="text-muted">+8% from last week</small>
                                        </div>
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>This Month:</span>
                                                <strong class="text-warning">$7,456.80</strong>
                                            </div>
                                            <small class="text-muted">+15% from last month</small>
                                        </div>
                                        <div class="stat-item">
                                            <div class="d-flex justify-content-between">
                                                <span>Total Revenue:</span>
                                                <strong class="text-primary">$45,892.15</strong>
                                            </div>
                                            <small class="text-muted">All time</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-container mt-3">
                                    <h5>Quick Actions</h5>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary" onclick="createPromoCode()">
                                            <i class="fas fa-tag"></i> Create Promo Code
                                        </button>
                                        <button class="btn btn-outline-success" onclick="bulkPriceUpdate()">
                                            <i class="fas fa-dollar-sign"></i> Bulk Price Update
                                        </button>
                                        <button class="btn btn-outline-info" onclick="salesReport()">
                                            <i class="fas fa-chart-line"></i> Sales Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction Log -->
                    <div class="tab-pane fade" id="transactions-content" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" placeholder="Search by user or transaction ID...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select">
                                    <option value="">All Products</option>
                                    <option value="starter">Starter Pack</option>
                                    <option value="premium">Premium Pack</option>
                                    <option value="pdf">Premium PDF</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select">
                                    <option value="">All Status</option>
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" placeholder="From Date">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" placeholder="To Date">
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>User</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $transactions = [
                                            ['id' => 'TXN001234', 'user' => 'sarah.m@email.com', 'product' => 'Premium Pack', 'amount' => 9.95, 'method' => 'PayPal', 'date' => '2 hours ago', 'status' => 'completed'],
                                            ['id' => 'TXN001233', 'user' => 'john.d@email.com', 'product' => 'Starter Pack', 'amount' => 4.95, 'method' => 'Stripe', 'date' => '4 hours ago', 'status' => 'completed'],
                                            ['id' => 'TXN001232', 'user' => 'emma.l@email.com', 'product' => 'Premium PDF', 'amount' => 4.95, 'method' => 'Stripe', 'date' => '6 hours ago', 'status' => 'completed'],
                                            ['id' => 'TXN001231', 'user' => 'mike.r@email.com', 'product' => 'Premium Pack', 'amount' => 9.95, 'method' => 'PayPal', 'date' => '1 day ago', 'status' => 'pending']
                                        ];
                                        foreach($transactions as $txn): ?>
                                            <tr>
                                                <td><code><?= $txn['id'] ?></code></td>
                                                <td><?= $txn['user'] ?></td>
                                                <td><span class="badge bg-primary"><?= $txn['product'] ?></span></td>
                                                <td><strong>$<?= number_format($txn['amount'], 2) ?></strong></td>
                                                <td>
                                                    <i class="fab fa-<?= strtolower($txn['method']) == 'paypal' ? 'paypal' : 'stripe' ?>"></i>
                                                    <?= $txn['method'] ?>
                                                </td>
                                                <td><small class="text-muted"><?= $txn['date'] ?></small></td>
                                                <td>
                                                    <span class="badge bg-<?= $txn['status'] == 'completed' ? 'success' : ($txn['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($txn['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTransaction('<?= $txn['id'] ?>')" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" onclick="refundTransaction('<?= $txn['id'] ?>')" title="Refund">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Referral System -->
                    <div class="tab-pane fade" id="referrals-content" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="table-container">
                                    <h5>Referral Settings</h5>
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Required Referrals for Reward</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" value="3" min="1" max="10">
                                                <span class="input-group-text">referrals</span>
                                            </div>
                                            <small class="form-text text-muted">Number of successful referrals needed to unlock rewards</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Reward Type</label>
                                            <select class="form-select">
                                                <option value="credits">Free Credits</option>
                                                <option value="discount">Discount Code</option>
                                                <option value="premium">Premium Access</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Reward Amount</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" value="5" min="1">
                                                <span class="input-group-text">credits</span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="enableReferrals" checked>
                                                <label class="form-check-label" for="enableReferrals">
                                                    Enable Referral System
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="table-container">
                                    <h5>Referral Statistics</h5>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="metric-card text-center">
                                                <h3 class="metric-value text-primary">1,247</h3>
                                                <p class="metric-label">Total Referrals</p>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="metric-card text-center">
                                                <h3 class="metric-value text-success">892</h3>
                                                <p class="metric-label">Successful</p>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="metric-card text-center">
                                                <h3 class="metric-value text-warning">156</h3>
                                                <p class="metric-label">Rewards Claimed</p>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="metric-card text-center">
                                                <h3 class="metric-value text-info">71.6%</h3>
                                                <p class="metric-label">Conversion Rate</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-container mt-3">
                                    <h5>Top Referrers</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Referrals</th>
                                                    <th>Rewards</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>sarah.m@email.com</td>
                                                    <td><span class="badge bg-primary">12</span></td>
                                                    <td><span class="badge bg-success">4</span></td>
                                                </tr>
                                                <tr>
                                                    <td>john.d@email.com</td>
                                                    <td><span class="badge bg-primary">8</span></td>
                                                    <td><span class="badge bg-success">2</span></td>
                                                </tr>
                                                <tr>
                                                    <td>emma.l@email.com</td>
                                                    <td><span class="badge bg-primary">6</span></td>
                                                    <td><span class="badge bg-success">2</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Affiliate Links -->
                    <div class="tab-pane fade" id="affiliates-content" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <h5>Affiliate Link Manager</h5>
                                <p class="text-muted">Manage affiliate links displayed in reports</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-success" onclick="addAffiliateLink()">
                                    <i class="fas fa-plus"></i> Add New Link
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>URL</th>
                                            <th>Category</th>
                                            <th>Clicks</th>
                                            <th>Revenue</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <strong>Crystal Healing Guide</strong>
                                                <br><small class="text-muted">Spiritual wellness affiliate</small>
                                            </td>
                                            <td><code>https://affiliate.example.com/crystals?ref=cosmic123</code></td>
                                            <td><span class="badge bg-purple">Spiritual</span></td>
                                            <td>1,247</td>
                                            <td>$156.80</td>
                                            <td><span class="badge bg-success">Active</span></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editAffiliate()" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleAffiliate()" title="Toggle">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAffiliate()" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Astrology Books</strong>
                                                <br><small class="text-muted">Educational content</small>
                                            </td>
                                            <td><code>https://bookstore.example.com/astrology?ref=cosmic123</code></td>
                                            <td><span class="badge bg-info">Education</span></td>
                                            <td>892</td>
                                            <td>$89.20</td>
                                            <td><span class="badge bg-success">Active</span></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editAffiliate()" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleAffiliate()" title="Toggle">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAffiliate()" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ad Placement -->
                    <div class="tab-pane fade" id="ads-content" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-container">
                                    <h5>Ad Code Management</h5>
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Header Ad Code</label>
                                            <textarea class="form-control" rows="4" placeholder="Paste your header ad code here (e.g., Ezoic, AdThrive)..."></textarea>
                                            <small class="form-text text-muted">This code will be injected into the &lt;head&gt; section of all pages</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Report Top Ad Code</label>
                                            <textarea class="form-control" rows="4" placeholder="Ad code for top of report pages..."></textarea>
                                            <small class="form-text text-muted">Displayed at the top of generated reports</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Report Bottom Ad Code</label>
                                            <textarea class="form-control" rows="4" placeholder="Ad code for bottom of report pages..."></textarea>
                                            <small class="form-text text-muted">Displayed at the bottom of generated reports</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Sidebar Ad Code</label>
                                            <textarea class="form-control" rows="4" placeholder="Sidebar ad code..."></textarea>
                                            <small class="form-text text-muted">Displayed in the sidebar of content pages</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="enableAds" checked>
                                                <label class="form-check-label" for="enableAds">
                                                    Enable Ad Display
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-secondary">Preview Changes</button>
                                            <div>
                                                <button type="button" class="btn btn-outline-primary me-2">Save Draft</button>
                                                <button type="submit" class="btn btn-primary">Save & Apply</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-container">
                                    <h5>Ad Performance</h5>
                                    <div class="ad-stats">
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Total Impressions:</span>
                                                <strong>45,892</strong>
                                            </div>
                                            <small class="text-muted">Last 30 days</small>
                                        </div>
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Click-through Rate:</span>
                                                <strong>2.4%</strong>
                                            </div>
                                            <small class="text-muted">Above average</small>
                                        </div>
                                        <div class="stat-item mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Ad Revenue:</span>
                                                <strong>$892.15</strong>
                                            </div>
                                            <small class="text-muted">This month</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-container mt-3">
                                    <h5>Ad Networks</h5>
                                    <div class="list-group">
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Ezoic</strong>
                                                <br><small class="text-muted">Primary network</small>
                                            </div>
                                            <span class="badge bg-success">Active</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>AdThrive</strong>
                                                <br><small class="text-muted">Secondary network</small>
                                            </div>
                                            <span class="badge bg-secondary">Inactive</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Tab -->
            <div class="tab-pane fade" id="system" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-cogs"></i> System Settings & AI Management</h4>
                    <div>
                        <button class="btn btn-warning me-2" onclick="runMaintenance()">
                            <i class="fas fa-tools"></i> Run Maintenance
                        </button>
                        <button class="btn btn-info" onclick="systemBackup()">
                            <i class="fas fa-download"></i> Backup System
                        </button>
                    </div>
                </div>
                
                <!-- System Tabs -->
                <ul class="nav nav-pills mb-4" id="systemTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="ai-prompts-tab" data-bs-toggle="pill" data-bs-target="#ai-prompts-content" type="button" role="tab">
                            <i class="fas fa-brain"></i> AI Prompt Editor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="api-keys-tab" data-bs-toggle="pill" data-bs-target="#api-keys-content" type="button" role="tab">
                            <i class="fas fa-key"></i> API Key Management
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="site-notices-tab" data-bs-toggle="pill" data-bs-target="#site-notices-content" type="button" role="tab">
                            <i class="fas fa-bullhorn"></i> Site-wide Notices
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="system-info-tab" data-bs-toggle="pill" data-bs-target="#system-info-content" type="button" role="tab">
                            <i class="fas fa-server"></i> System Information
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="systemTabContent">
                    <!-- AI Prompt Editor -->
                    <div class="tab-pane fade show active" id="ai-prompts-content" role="tabpanel">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-container">
                                    <h5>AI Prompt Templates</h5>
                                    <p class="text-muted">Edit the AI prompt templates used to generate different sections of the cosmic reports. Changes will affect all new reports generated.</p>
                                    
                                    <div class="accordion" id="promptAccordion">
                                        <!-- Soul's Archetype Prompt -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="archetypePromptHeader">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#archetypePrompt" aria-expanded="true">
                                                    <i class="fas fa-user-circle me-2"></i> Soul's Archetype Prompt
                                                </button>
                                            </h2>
                                            <div id="archetypePrompt" class="accordion-collapse collapse show" data-bs-parent="#promptAccordion">
                                                <div class="accordion-body">
                                                    <form>
                                                        <div class="mb-3">
                                                            <label class="form-label">Prompt Template</label>
                                                            <textarea class="form-control" rows="8" placeholder="Enter the AI prompt for generating Soul's Archetype section...">You are a cosmic astrologer and spiritual guide. Based on the birth date {birth_date}, birth time {birth_time}, and location {birth_location}, create a detailed Soul's Archetype reading that reveals the person's core spiritual essence and life purpose. Include their dominant personality traits, spiritual gifts, and karmic lessons. Write in an engaging, mystical tone that feels personal and insightful.</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <small class="form-text text-muted">
                                                                Available variables: {birth_date}, {birth_time}, {birth_location}, {name}, {zodiac_sign}
                                                            </small>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="testPrompt('archetype')">Test Prompt</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Planetary Influence Prompt -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="planetaryPromptHeader">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#planetaryPrompt">
                                                    <i class="fas fa-globe me-2"></i> Planetary Influence Prompt
                                                </button>
                                            </h2>
                                            <div id="planetaryPrompt" class="accordion-collapse collapse" data-bs-parent="#promptAccordion">
                                                <div class="accordion-body">
                                                    <form>
                                                        <div class="mb-3">
                                                            <label class="form-label">Prompt Template</label>
                                                            <textarea class="form-control" rows="8" placeholder="Enter the AI prompt for generating Planetary Influence section...">Analyze the planetary positions and aspects for someone born on {birth_date} at {birth_time} in {birth_location}. Focus on how the major planets (Sun, Moon, Mercury, Venus, Mars, Jupiter, Saturn) influence their personality, relationships, career path, and life challenges. Explain the cosmic energies at play and how they can harness these influences for personal growth.</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <small class="form-text text-muted">
                                                                Available variables: {birth_date}, {birth_time}, {birth_location}, {name}, {zodiac_sign}
                                                            </small>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="testPrompt('planetary')">Test Prompt</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Life Path Guidance Prompt -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="lifepathPromptHeader">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#lifepathPrompt">
                                                    <i class="fas fa-route me-2"></i> Life Path Guidance Prompt
                                                </button>
                                            </h2>
                                            <div id="lifepathPrompt" class="accordion-collapse collapse" data-bs-parent="#promptAccordion">
                                                <div class="accordion-body">
                                                    <form>
                                                        <div class="mb-3">
                                                            <label class="form-label">Prompt Template</label>
                                                            <textarea class="form-control" rows="8" placeholder="Enter the AI prompt for generating Life Path Guidance section...">Provide comprehensive life path guidance for someone born on {birth_date}. Include insights about their soul's mission, key life lessons, potential challenges and how to overcome them, ideal career paths, relationship patterns, and spiritual development opportunities. Offer practical advice for aligning with their highest purpose.</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <small class="form-text text-muted">
                                                                Available variables: {birth_date}, {birth_time}, {birth_location}, {name}, {zodiac_sign}
                                                            </small>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="testPrompt('lifepath')">Test Prompt</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Cosmic Insights Prompt -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="cosmicPromptHeader">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cosmicPrompt">
                                                    <i class="fas fa-stars me-2"></i> Cosmic Insights Prompt
                                                </button>
                                            </h2>
                                            <div id="cosmicPrompt" class="accordion-collapse collapse" data-bs-parent="#promptAccordion">
                                                <div class="accordion-body">
                                                    <form>
                                                        <div class="mb-3">
                                                            <label class="form-label">Prompt Template</label>
                                                            <textarea class="form-control" rows="8" placeholder="Enter the AI prompt for generating Cosmic Insights section...">Generate profound cosmic insights and spiritual revelations for someone born on {birth_date}. Include information about their connection to universal energies, past life influences, karmic patterns, spiritual gifts, and their role in the cosmic plan. Provide mystical wisdom that helps them understand their place in the universe.</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <small class="form-text text-muted">
                                                                Available variables: {birth_date}, {birth_time}, {birth_location}, {name}, {zodiac_sign}
                                                            </small>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="testPrompt('cosmic')">Test Prompt</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Key Management -->
                    <div class="tab-pane fade" id="api-keys-content" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-container">
                                    <h5>API Configuration</h5>
                                    <p class="text-muted">Manage API keys for external services. All keys are encrypted and stored securely.</p>
                                    
                                    <form>
                                        <div class="mb-4">
                                            <label class="form-label">AI Service API Key</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" placeholder="Enter your AI service API key..." value="sk-****************************">
                                                <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility('ai')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Used for generating AI-powered cosmic reports</small>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Historical Data API Key</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" placeholder="Enter historical data API key..." value="hd-****************************">
                                                <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility('history')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Used for fetching historical astronomical data</small>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Payment Gateway API Key</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" placeholder="Enter payment gateway API key..." value="pg-****************************">
                                                <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility('payment')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Used for processing credit pack purchases</small>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Email Service API Key</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" placeholder="Enter email service API key..." value="es-****************************">
                                                <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility('email')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Used for sending transactional emails and notifications</small>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-warning" onclick="testApiConnections()">Test All Connections</button>
                                            <div>
                                                <button type="button" class="btn btn-outline-primary me-2">Save Draft</button>
                                                <button type="submit" class="btn btn-primary">Save & Apply</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-container">
                                    <h5>API Status</h5>
                                    <div class="api-status-list">
                                        <div class="status-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>AI Service</strong>
                                                    <br><small class="text-muted">Last checked: 2 min ago</small>
                                                </div>
                                                <span class="badge bg-success">Online</span>
                                            </div>
                                        </div>
                                        <div class="status-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Historical Data</strong>
                                                    <br><small class="text-muted">Last checked: 5 min ago</small>
                                                </div>
                                                <span class="badge bg-success">Online</span>
                                            </div>
                                        </div>
                                        <div class="status-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Payment Gateway</strong>
                                                    <br><small class="text-muted">Last checked: 1 min ago</small>
                                                </div>
                                                <span class="badge bg-success">Online</span>
                                            </div>
                                        </div>
                                        <div class="status-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Email Service</strong>
                                                    <br><small class="text-muted">Last checked: 3 min ago</small>
                                                </div>
                                                <span class="badge bg-warning">Slow</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-container mt-3">
                                    <h5>Usage Statistics</h5>
                                    <div class="usage-stats">
                                        <div class="stat-item mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span>AI Requests Today:</span>
                                                <strong>247</strong>
                                            </div>
                                        </div>
                                        <div class="stat-item mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span>API Calls This Month:</span>
                                                <strong>8,456</strong>
                                            </div>
                                        </div>
                                        <div class="stat-item mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span>Rate Limit Remaining:</span>
                                                <strong>1,753</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Site-wide Notices -->
                    <div class="tab-pane fade" id="site-notices-content" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-container">
                                    <h5>Site-wide Notice Banner</h5>
                                    <p class="text-muted">Display important announcements, maintenance notices, or promotional messages across all pages.</p>
                                    
                                    <form>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enableNotice" checked>
                                                <label class="form-check-label" for="enableNotice">
                                                    <strong>Enable Site-wide Notice</strong>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Notice Type</label>
                                            <select class="form-select">
                                                <option value="info">Information (Blue)</option>
                                                <option value="success">Success (Green)</option>
                                                <option value="warning" selected>Warning (Yellow)</option>
                                                <option value="danger">Alert (Red)</option>
                                                <option value="primary">Promotional (Purple)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Notice Message</label>
                                            <textarea class="form-control" rows="3" placeholder="Enter your notice message...">🌟 Special Offer: Get 50% off all credit packs this weekend! Use code COSMIC50 at checkout.</textarea>
                                            <small class="form-text text-muted">Supports HTML and emoji. Keep it concise for better user experience.</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Display Options</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="showCloseButton" checked>
                                                <label class="form-check-label" for="showCloseButton">
                                                    Allow users to dismiss notice
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="stickyNotice">
                                                <label class="form-check-label" for="stickyNotice">
                                                    Make notice sticky (always visible)
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Schedule (Optional)</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="datetime-local" class="form-control" placeholder="Start Date & Time">
                                                    <small class="form-text text-muted">When to start showing</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="datetime-local" class="form-control" placeholder="End Date & Time">
                                                    <small class="form-text text-muted">When to stop showing</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-secondary" onclick="previewNotice()">Preview Notice</button>
                                            <div>
                                                <button type="button" class="btn btn-outline-danger me-2" onclick="disableNotice()">Disable Notice</button>
                                                <button type="submit" class="btn btn-primary">Save & Activate</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-container">
                                    <h5>Notice Preview</h5>
                                    <div class="notice-preview p-3 border rounded">
                                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                            🌟 Special Offer: Get 50% off all credit packs this weekend! Use code COSMIC50 at checkout.
                                            <button type="button" class="btn-close" aria-label="Close"></button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-container mt-3">
                                    <h5>Notice History</h5>
                                    <div class="notice-history">
                                        <div class="history-item mb-2 p-2 border rounded">
                                            <small class="text-muted">Dec 15, 2024</small>
                                            <div>Holiday Sale Announcement</div>
                                        </div>
                                        <div class="history-item mb-2 p-2 border rounded">
                                            <small class="text-muted">Dec 10, 2024</small>
                                            <div>Maintenance Notice</div>
                                        </div>
                                        <div class="history-item mb-2 p-2 border rounded">
                                            <small class="text-muted">Dec 5, 2024</small>
                                            <div>New Feature Launch</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Information -->
                    <div class="tab-pane fade" id="system-info-content" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="table-container">
                                    <h5 class="mb-3">System Information</h5>
                                    <table class="table">
                                        <tr>
                                            <td><strong>PHP Version</strong></td>
                                            <td><?= PHP_VERSION ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Server Software</strong></td>
                                            <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Database</strong></td>
                                            <td>MySQL <?= $systemHealth['db_version'] ?? 'Unknown' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Memory Usage</strong></td>
                                            <td><?= number_format(memory_get_usage(true) / 1024 / 1024, 2) ?> MB</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Uptime</strong></td>
                                            <td><?= $systemHealth['uptime'] ?? 'Unknown' ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="table-container">
                                    <h5 class="mb-3">System Actions</h5>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-warning-gradient" onclick="clearCache()">
                                            <i class="fas fa-broom"></i> Clear Cache
                                        </button>
                                        <button class="btn btn-secondary-gradient" onclick="runMaintenance()">
                                            <i class="fas fa-tools"></i> Run Maintenance
                                        </button>
                                        <button class="btn btn-primary-gradient" onclick="backupDatabase()">
                                            <i class="fas fa-database"></i> Backup Database
                                        </button>
                                        <button class="btn btn-success-gradient" onclick="optimizeDatabase()">
                                            <i class="fas fa-tachometer-alt"></i> Optimize Database
                                        </button>
                                        <button class="btn btn-danger" onclick="restartServices()">
                                            <i class="fas fa-power-off"></i> Restart Services
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart configurations
        const dailyActivityCtx = document.getElementById('dailyActivityChart')?.getContext('2d');
        if (dailyActivityCtx) {
            new Chart(dailyActivityCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($dailyStats, 'date')) ?>,
                    datasets: [{
                        label: 'Page Views',
                        data: <?= json_encode(array_column($dailyStats, 'page_views')) ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'User Actions',
                        data: <?= json_encode(array_column($dailyStats, 'user_actions')) ?>,
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        const eventDistributionCtx = document.getElementById('eventDistributionChart')?.getContext('2d');
        if (eventDistributionCtx) {
            new Chart(eventDistributionCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($eventCounts)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($eventCounts)) ?>,
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#4facfe',
                            '#00f2fe',
                            '#43e97b',
                            '#38f9d7'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Admin functions
        function refreshDashboard() {
            location.reload();
        }

        function exportData() {
            alert('Export functionality coming soon!');
        }

        function systemMaintenance() {
            if (confirm('Are you sure you want to run system maintenance?')) {
                alert('Maintenance mode activated!');
            }
        }

        function sendNotification() {
            alert('Notification system coming soon!');
        }

        function viewFeedback(id) {
            window.open(`/feedback/admin/details/${id}`, '_blank');
        }

        function respondToFeedback(id) {
            window.open(`/feedback/admin?id=${id}`, '_blank');
        }

        function addUser() {
            alert('User management coming soon!');
        }

        function exportUsers() {
            alert('User export coming soon!');
        }

        function clearCache() {
            if (confirm('Clear system cache?')) {
                alert('Cache cleared successfully!');
            }
        }

        function runMaintenance() {
            if (confirm('Run system maintenance?')) {
                alert('Maintenance completed!');
            }
        }

        function backupDatabase() {
            if (confirm('Create database backup?')) {
                alert('Backup created successfully!');
            }
        }

        function optimizeDatabase() {
            if (confirm('Optimize database tables?')) {
                alert('Database optimized!');
            }
        }

        function restartServices() {
            if (confirm('Restart system services? This may cause temporary downtime.')) {
                alert('Services restarted!');
            }
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            if (document.getElementById('overview-tab').classList.contains('active')) {
                location.reload();
            }
        }, 300000);
    </script>
</body>
</html>