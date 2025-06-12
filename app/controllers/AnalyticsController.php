<?php
/**
 * AnalyticsController
 * 
 * Handles analytics dashboard and API endpoints for Phase 3 beta testing monitoring
 */

namespace App\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\BetaTestMetrics;
use App\Models\UserFeedback;
use App\Models\User;
use App\Services\AnalyticsService;

class AnalyticsController extends BaseController
{
    private $analyticsModel;
    private $metricsModel;
    private $feedbackModel;
    private $userModel;
    private $analyticsService;
    
    public function __construct()
    {
        parent::__construct();
        $this->analyticsModel = new AnalyticsEvent();
        $this->metricsModel = new BetaTestMetrics();
        $this->feedbackModel = new UserFeedback();
        $this->userModel = new User();
        $this->analyticsService = new AnalyticsService();
    }
    
    /**
     * Analytics dashboard (admin only)
     */
    public function dashboard()
    {
        try {
            // Check admin permissions
            $user = $this->getAuthenticatedUser();
            if (!$user || !$this->isAdmin($user)) {
                $this->redirect('/dashboard');
                return;
            }
            
            // Track dashboard access
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_PAGE_VIEW,
                ['page' => 'analytics_dashboard'],
                ['page_load_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']]
            );
            
            // Get date range from query parameters
            $days = (int)($_GET['days'] ?? 7);
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            
            // Get dashboard summary
            $summary = $this->metricsModel->getDashboardSummary($days);
            
            // Get recent analytics events
            $recentEvents = $this->analyticsModel->getByDateRange($startDate, $endDate);
            
            // Get event counts
            $eventCounts = $this->analyticsModel->getEventCounts($startDate, $endDate);
            
            // Get daily stats
            $dailyStats = $this->analyticsModel->getDailyStats($days);
            
            // Get user engagement metrics
            $userEngagement = $this->analyticsModel->getUserEngagementMetrics();
            
            // Get performance metrics
            $performanceMetrics = $this->analyticsModel->getPerformanceMetrics($startDate, $endDate);
            
            // Get recent feedback
            $recentFeedback = $this->feedbackModel->getRecent(10);
            
            // Get feedback statistics
            $feedbackStats = $this->feedbackModel->getStatistics($startDate, $endDate);
            
            $data = [
                'title' => 'Analytics Dashboard - CosmicHub Beta',
                'summary' => $summary,
                'recentEvents' => array_slice($recentEvents, 0, 50), // Limit to 50 recent events
                'eventCounts' => $eventCounts,
                'dailyStats' => $dailyStats,
                'userEngagement' => array_slice($userEngagement, 0, 20), // Top 20 users
                'performanceMetrics' => $performanceMetrics,
                'recentFeedback' => $recentFeedback,
                'feedbackStats' => $feedbackStats,
                'dateRange' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'days' => $days
                ]
            ];
            
            $this->view('analytics/dashboard', $data);
        } catch (Exception $e) {
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_ERROR,
                ['error' => 'analytics_dashboard_error', 'message' => $e->getMessage()]
            );
            $this->handleError('Error loading analytics dashboard');
        }
    }
    
    /**
     * API endpoint for real-time metrics
     */
    public function api()
    {
        try {
            // Check admin permissions
            $user = $this->getAuthenticatedUser();
            if (!$user || !$this->isAdmin($user)) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            $endpoint = $_GET['endpoint'] ?? '';
            $days = (int)($_GET['days'] ?? 7);
            
            header('Content-Type: application/json');
            
            switch ($endpoint) {
                case 'summary':
                    echo json_encode($this->metricsModel->getDashboardSummary($days));
                    break;
                    
                case 'events':
                    $eventType = $_GET['type'] ?? null;
                    $limit = (int)($_GET['limit'] ?? 100);
                    $events = $eventType 
                        ? $this->analyticsModel->getByType($eventType, $limit)
                        : $this->analyticsModel->getByDateRange(
                            date('Y-m-d', strtotime("-{$days} days")),
                            date('Y-m-d')
                        );
                    echo json_encode($events);
                    break;
                    
                case 'metrics':
                    $metricName = $_GET['metric'] ?? '';
                    if ($metricName) {
                        $metrics = $this->metricsModel->getMetric(
                            $metricName,
                            date('Y-m-d', strtotime("-{$days} days")),
                            date('Y-m-d')
                        );
                        echo json_encode($metrics);
                    } else {
                        echo json_encode(['error' => 'Metric name required']);
                    }
                    break;
                    
                case 'trends':
                    $metricName = $_GET['metric'] ?? '';
                    if ($metricName) {
                        $trends = $this->metricsModel->getMetricTrends($metricName, $days);
                        echo json_encode($trends);
                    } else {
                        echo json_encode(['error' => 'Metric name required']);
                    }
                    break;
                    
                case 'performance':
                    $performance = $this->analyticsModel->getPerformanceMetrics(
                        date('Y-m-d', strtotime("-{$days} days")),
                        date('Y-m-d')
                    );
                    echo json_encode($this->processPerformanceData($performance));
                    break;
                    
                case 'user_engagement':
                    $engagement = $this->analyticsModel->getUserEngagementMetrics();
                    echo json_encode(array_slice($engagement, 0, 50)); // Top 50 users
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid endpoint']);
                    break;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    /**
     * Track event endpoint (for JavaScript tracking)
     */
    public function track()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['event_type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }
            
            // Track the event
            $result = $this->analyticsService->trackEvent(
                $input['event_type'],
                $input['event_data'] ?? [],
                $input['metadata'] ?? []
            );
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to track event']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    /**
     * Generate daily metrics report
     */
    public function generateDailyReport()
    {
        try {
            // Check admin permissions
            $user = $this->getAuthenticatedUser();
            if (!$user || !$this->isAdmin($user)) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            $date = $_GET['date'] ?? date('Y-m-d');
            
            // Generate the report
            $report = $this->metricsModel->generateDailyReport($date);
            
            // Track report generation
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_USER_ACTION,
                ['action' => 'daily_report_generated', 'date' => $date]
            );
            
            header('Content-Type: application/json');
            echo json_encode($report);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to generate report']);
        }
    }
    
    /**
     * Export analytics data
     */
    public function export()
    {
        try {
            // Check admin permissions
            $user = $this->getAuthenticatedUser();
            if (!$user || !$this->isAdmin($user)) {
                $this->redirect('/dashboard');
                return;
            }
            
            $format = $_GET['format'] ?? 'csv';
            $type = $_GET['type'] ?? 'events';
            $days = (int)($_GET['days'] ?? 30);
            
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            $endDate = date('Y-m-d');
            
            switch ($type) {
                case 'events':
                    $data = $this->analyticsModel->getByDateRange($startDate, $endDate);
                    $filename = "analytics_events_{$startDate}_to_{$endDate}";
                    break;
                    
                case 'metrics':
                    $data = $this->getAllMetricsForPeriod($startDate, $endDate);
                    $filename = "beta_metrics_{$startDate}_to_{$endDate}";
                    break;
                    
                case 'feedback':
                    $data = $this->feedbackModel->getAll([
                        'date_from' => $startDate,
                        'date_to' => $endDate
                    ], 1000);
                    $filename = "user_feedback_{$startDate}_to_{$endDate}";
                    break;
                    
                default:
                    $this->handleError('Invalid export type');
                    return;
            }
            
            if ($format === 'csv') {
                $this->exportToCsv($data, $filename);
            } else {
                $this->exportToJson($data, $filename);
            }
            
            // Track export
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_USER_ACTION,
                [
                    'action' => 'data_exported',
                    'type' => $type,
                    'format' => $format,
                    'date_range' => "{$startDate} to {$endDate}"
                ]
            );
        } catch (Exception $e) {
            $this->handleError('Error exporting data');
        }
    }
    
    /**
     * Process performance data for visualization
     */
    private function processPerformanceData($performanceMetrics)
    {
        $processed = [
            'features' => [],
            'average_times' => [],
            'trends' => []
        ];
        
        foreach ($performanceMetrics as $metric) {
            $feature = $metric['feature'];
            $time = $metric['execution_time'];
            
            if (!isset($processed['features'][$feature])) {
                $processed['features'][$feature] = [
                    'total_calls' => 0,
                    'total_time' => 0,
                    'min_time' => $time,
                    'max_time' => $time,
                    'times' => []
                ];
            }
            
            $processed['features'][$feature]['total_calls']++;
            $processed['features'][$feature]['total_time'] += $time;
            $processed['features'][$feature]['min_time'] = min($processed['features'][$feature]['min_time'], $time);
            $processed['features'][$feature]['max_time'] = max($processed['features'][$feature]['max_time'], $time);
            $processed['features'][$feature]['times'][] = $time;
        }
        
        // Calculate averages
        foreach ($processed['features'] as $feature => $data) {
            $processed['average_times'][$feature] = $data['total_time'] / $data['total_calls'];
        }
        
        return $processed;
    }
    
    /**
     * Get all metrics for a period
     */
    private function getAllMetricsForPeriod($startDate, $endDate)
    {
        $metrics = [];
        $metricTypes = [
            BetaTestMetrics::METRIC_DAILY_ACTIVE_USERS,
            BetaTestMetrics::METRIC_ERROR_RATE,
            BetaTestMetrics::METRIC_USER_SATISFACTION,
            BetaTestMetrics::METRIC_FEATURE_ADOPTION
        ];
        
        foreach ($metricTypes as $type) {
            $typeMetrics = $this->metricsModel->getMetric($type, $startDate, $endDate);
            $metrics = array_merge($metrics, $typeMetrics);
        }
        
        return $metrics;
    }
    
    /**
     * Export data to CSV
     */
    private function exportToCsv($data, $filename)
    {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename={$filename}.csv");
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Write headers
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
    }
    
    /**
     * Export data to JSON
     */
    private function exportToJson($data, $filename)
    {
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename={$filename}.json");
        
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin($user)
    {
        return isset($user['is_admin']) && $user['is_admin'] == 1;
    }
    
    /**
     * Get authenticated user
     */
    private function getAuthenticatedUser()
    {
        if (isset($_SESSION['user_id'])) {
            return $this->userModel->findById($_SESSION['user_id']);
        }
        return null;
    }
}