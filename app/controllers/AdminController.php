<?php
/**
 * Unified Admin Controller
 * 
 * Comprehensive admin controller combining analytics, feedback management,
 * user management, content management, and system monitoring
 */

class AdminController {
    private $db;
    private $user;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->user = $_SESSION['user'] ?? null;
    }
    
    /**
     * Check if current user is admin
     */
    private function isAdmin() {
        return $this->user && isset($this->user['is_admin']) && $this->user['is_admin'] == 1;
    }
    
    /**
     * Main admin dashboard
     */
    public function dashboard() {
        try {
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Admin privileges required.']);
                return;
            }
            
            // Get date range from request or default to last 7 days
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $days = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
            
            $dateRange = [
                'start' => $startDate,
                'end' => $endDate,
                'days' => $days
            ];
            
            // Gather all dashboard data
            $data = [
                'title' => 'Admin Dashboard - CosmicHub',
                'summary' => $this->getSummaryData($startDate, $endDate),
                'recentEvents' => $this->getRecentEvents($startDate, $endDate),
                'eventCounts' => $this->getEventCounts($startDate, $endDate),
                'dailyStats' => $this->getDailyStats($startDate, $endDate),
                'userEngagement' => $this->getUserEngagement($startDate, $endDate),
                'performanceMetrics' => $this->getPerformanceMetrics($startDate, $endDate),
                'recentFeedback' => $this->getRecentFeedback(),
                'feedbackStats' => $this->getFeedbackStats(),
                'userStats' => $this->getUserStats(),
                'systemHealth' => $this->getSystemHealth(),
                'contentStats' => $this->getContentStats(),
                'dateRange' => $dateRange
            ];
            
            // Load the unified dashboard view
            extract($data);
            require_once __DIR__ . '/../views/admin/dashboard.php';
            
        } catch (Exception $e) {
            error_log("Admin Dashboard Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    /**
     * Get summary statistics
     */
    private function getSummaryData($startDate, $endDate) {
        try {
            $summary = [];
            
            // Total users
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $summary['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // New users today
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
            $stmt->execute();
            $summary['new_users_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total reports (if celebrity_reports table exists)
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM celebrity_reports");
                $stmt->execute();
                $summary['total_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Reports today
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM celebrity_reports WHERE DATE(created_at) = CURDATE()");
                $stmt->execute();
                $summary['reports_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (Exception $e) {
                $summary['total_reports'] = 0;
                $summary['reports_today'] = 0;
            }
            
            // Page views today (if analytics table exists)
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total 
                    FROM analytics_events 
                    WHERE event_type = 'page_view' 
                    AND DATE(created_at) = CURDATE()
                ");
                $stmt->execute();
                $summary['page_views_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Average session duration
                $stmt = $this->db->prepare("
                    SELECT AVG(JSON_EXTRACT(performance_data, '$.page_load_time')) as avg_duration
                    FROM analytics_events 
                    WHERE performance_data IS NOT NULL
                    AND DATE(created_at) = CURDATE()
                ");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $summary['avg_session_duration'] = round($result['avg_duration'] * 1000) ?? 0;
            } catch (Exception $e) {
                $summary['page_views_today'] = 0;
                $summary['avg_session_duration'] = 0;
            }
            
            return $summary;
            
        } catch (Exception $e) {
            error_log("Error getting summary data: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent events from analytics
     */
    private function getRecentEvents($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM analytics_events 
                WHERE created_at BETWEEN ? AND ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            
            $events = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['event_data'] = json_decode($row['event_data'] ?? '{}', true);
                $row['performance_data'] = json_decode($row['performance_data'] ?? '{}', true);
                $events[] = $row;
            }
            
            return $events;
            
        } catch (Exception $e) {
            error_log("Error getting recent events: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get event type counts
     */
    private function getEventCounts($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT event_type, COUNT(*) as count 
                FROM analytics_events 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY event_type 
                ORDER BY count DESC
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            
            $counts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $counts[$row['event_type']] = (int)$row['count'];
            }
            
            return $counts;
            
        } catch (Exception $e) {
            error_log("Error getting event counts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get daily statistics
     */
    private function getDailyStats($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) as page_views,
                    SUM(CASE WHEN event_type != 'page_view' THEN 1 ELSE 0 END) as user_actions
                FROM analytics_events 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY DATE(created_at) 
                ORDER BY date
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting daily stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user engagement metrics
     */
    private function getUserEngagement($startDate, $endDate) {
        try {
            $engagement = [];
            
            // Active users
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_id) as active_users 
                FROM analytics_events 
                WHERE created_at BETWEEN ? AND ? 
                AND user_id IS NOT NULL
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $engagement['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];
            
            // Average session length
            $stmt = $this->db->prepare("
                SELECT AVG(session_duration) as avg_session 
                FROM (
                    SELECT user_id, 
                           TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as session_duration
                    FROM analytics_events 
                    WHERE created_at BETWEEN ? AND ? 
                    AND user_id IS NOT NULL
                    GROUP BY user_id, DATE(created_at)
                ) as sessions
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $engagement['avg_session_duration'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_session'] ?? 0);
            
            return $engagement;
            
        } catch (Exception $e) {
            error_log("Error getting user engagement: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($startDate, $endDate) {
        try {
            $metrics = [];
            
            // Average response time
            $stmt = $this->db->prepare("
                SELECT AVG(JSON_EXTRACT(performance_data, '$.page_load_time')) as avg_response_time
                FROM analytics_events 
                WHERE performance_data IS NOT NULL
                AND created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $metrics['avg_response_time'] = round(($result['avg_response_time'] ?? 0) * 1000);
            
            // Error rate
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN event_type = 'error' THEN 1 ELSE 0 END) as errors
                FROM analytics_events 
                WHERE created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $metrics['error_rate'] = $result['total_requests'] > 0 ? 
                round(($result['errors'] / $result['total_requests']) * 100, 2) : 0;
            
            return $metrics;
            
        } catch (Exception $e) {
            error_log("Error getting performance metrics: " . $e->getMessage());
            return ['avg_response_time' => 0, 'error_rate' => 0];
        }
    }
    
    /**
     * Get recent feedback
     */
    private function getRecentFeedback() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM user_feedback 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting recent feedback: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get feedback statistics
     */
    private function getFeedbackStats() {
        try {
            $stats = [];
            
            // Total feedback
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM user_feedback");
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Status counts
            $stmt = $this->db->prepare("
                SELECT status, COUNT(*) as count 
                FROM user_feedback 
                GROUP BY status
            ");
            $stmt->execute();
            
            $stats['pending'] = 0;
            $stats['in_progress'] = 0;
            $stats['resolved'] = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats[$row['status']] = (int)$row['count'];
            }
            
            // Average rating
            $stmt = $this->db->prepare("
                SELECT AVG(rating) as avg_rating 
                FROM user_feedback 
                WHERE rating IS NOT NULL
            ");
            $stmt->execute();
            $stats['avg_rating'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0, 1);
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting feedback stats: " . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'avg_rating' => 0];
        }
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats() {
        try {
            $stats = [];
            
            // Total users
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active users (logged in within last 30 days)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as active 
                FROM users 
                WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'] ?? 0;
            
            // New users today
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as new_today 
                FROM users 
                WHERE DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            $stats['new_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_today'];
            
            // Premium users (if premium column exists)
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as premium 
                    FROM users 
                    WHERE is_premium = 1
                ");
                $stmt->execute();
                $stats['premium'] = $stmt->fetch(PDO::FETCH_ASSOC)['premium'];
            } catch (Exception $e) {
                $stats['premium'] = 0;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting user stats: " . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'new_today' => 0, 'premium' => 0];
        }
    }
    
    /**
     * Get system health information
     */
    private function getSystemHealth() {
        try {
            $health = [];
            
            // Database version
            $stmt = $this->db->prepare("SELECT VERSION() as version");
            $stmt->execute();
            $health['db_version'] = $stmt->fetch(PDO::FETCH_ASSOC)['version'];
            
            // System uptime (placeholder)
            $health['uptime'] = '5 days, 12 hours';
            
            // Storage usage (placeholder)
            $health['storage_usage'] = 78;
            
            return $health;
            
        } catch (Exception $e) {
            error_log("Error getting system health: " . $e->getMessage());
            return ['db_version' => 'Unknown', 'uptime' => 'Unknown', 'storage_usage' => 0];
        }
    }
    
    /**
     * Get content statistics
     */
    private function getContentStats() {
        try {
            $stats = [];
            
            // Total reports
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM celebrity_reports");
                $stmt->execute();
                $stats['total_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (Exception $e) {
                $stats['total_reports'] = 0;
            }
            
            // Celebrities count
            try {
                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT celebrity_name) as count FROM celebrity_reports");
                $stmt->execute();
                $stats['celebrities'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                $stats['celebrities'] = 0;
            }
            
            // Archetypes (placeholder)
            $stats['archetypes'] = 12;
            
            // Daily vibes (placeholder)
            $stats['daily_vibes'] = 365;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting content stats: " . $e->getMessage());
            return ['total_reports' => 0, 'celebrities' => 0, 'archetypes' => 0, 'daily_vibes' => 0];
        }
    }
    
    /**
     * API endpoint for dashboard data
     */
    public function api() {
        try {
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $data = [
                'summary' => $this->getSummaryData($startDate, $endDate),
                'recentEvents' => $this->getRecentEvents($startDate, $endDate),
                'eventCounts' => $this->getEventCounts($startDate, $endDate),
                'dailyStats' => $this->getDailyStats($startDate, $endDate),
                'userEngagement' => $this->getUserEngagement($startDate, $endDate),
                'performanceMetrics' => $this->getPerformanceMetrics($startDate, $endDate),
                'feedbackStats' => $this->getFeedbackStats(),
                'userStats' => $this->getUserStats(),
                'systemHealth' => $this->getSystemHealth(),
                'contentStats' => $this->getContentStats()
            ];
            
            header('Content-Type: application/json');
            echo json_encode($data);
            
        } catch (Exception $e) {
            error_log("Admin API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    /**
     * Export data functionality
     */
    public function export() {
        try {
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }
            
            $type = $_GET['type'] ?? 'all';
            $format = $_GET['format'] ?? 'csv';
            
            switch ($type) {
                case 'users':
                    $this->exportUsers($format);
                    break;
                case 'feedback':
                    $this->exportFeedback($format);
                    break;
                case 'analytics':
                    $this->exportAnalytics($format);
                    break;
                default:
                    $this->exportAll($format);
            }
            
        } catch (Exception $e) {
            error_log("Export Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Export failed']);
        }
    }
    
    /**
     * Export users data
     */
    private function exportUsers($format = 'csv') {
        $stmt = $this->db->prepare("
            SELECT id, username, email, created_at, last_login, is_admin 
            FROM users 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Username', 'Email', 'Created At', 'Last Login', 'Is Admin']);
            
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $user['created_at'],
                    $user['last_login'],
                    $user['is_admin'] ? 'Yes' : 'No'
                ]);
            }
            
            fclose($output);
        }
    }
    
    /**
     * Export feedback data
     */
    private function exportFeedback($format = 'csv') {
        $stmt = $this->db->prepare("
            SELECT * FROM user_feedback 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            if (!empty($feedback)) {
                fputcsv($output, array_keys($feedback[0]));
                foreach ($feedback as $row) {
                    fputcsv($output, $row);
                }
            }
            fclose($output);
        }
    }
    
    /**
     * Export analytics data
     */
    private function exportAnalytics($format = 'csv') {
        $stmt = $this->db->prepare("
            SELECT * FROM analytics_events 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="analytics_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            if (!empty($analytics)) {
                fputcsv($output, array_keys($analytics[0]));
                foreach ($analytics as $row) {
                    fputcsv($output, $row);
                }
            }
            fclose($output);
        }
    }
    
    /**
     * Export all data
     */
    private function exportAll($format = 'csv') {
        // For now, just export users as an example
        $this->exportUsers($format);
    }
}