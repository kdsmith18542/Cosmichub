<?php
/**
 * AnalyticsController
 * 
 * Handles analytics dashboard and API endpoints for Phase 3 beta testing monitoring
 */

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Exception; // Keep Exception for try-catch blocks

class AnalyticsController extends Controller
{
    private $analyticsService;
    private $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->analyticsService = $this->resolve(AnalyticsService::class);
        $this->logger = $logger;
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin(?object $user = null): bool // Assuming $user can be null if not logged in
    {
        $currentUser = $user ?: $this->getCurrentUser();
        if (!$currentUser) {
            return false;
        }
        // Assuming AdminService has an isAdmin method or similar logic
        // Or, if User model has an isAdmin property/method:
        // return $currentUser->is_admin ?? false; 
        // For now, let's assume a method in AnalyticsService or a direct check if applicable
        // This part needs to be adapted based on how admin status is actually determined.
        // Placeholder, assuming a direct property or a method on user model for simplicity here.
        // In a real scenario, this would likely involve checking a role or a specific flag.
        return isset($currentUser->is_admin) && $currentUser->is_admin === true;
    }
    
    /**
     * Analytics dashboard (admin only)
     */
    public function dashboard(Request $request): Response
    {
        try {
            // Check admin permissions
            if (!$this->isLoggedIn()) {
                return $this->redirect('/login');
            }
            
            if (!$this->isAdmin()) {
                return $this->redirect('/dashboard');
            }
            
            // Track dashboard access
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_PAGE_VIEW,
                ['page' => 'analytics_dashboard'],
                ['page_load_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))]
            );
            
            // Get date range from query parameters
            $days = (int)$request->get('days', 7);
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays($days);
            
            // Use AnalyticsService for all data fetching
            $summary = $this->analyticsService->getDashboardSummary($startDate, $endDate);
            $recentEvents = $this->analyticsService->getRecentEvents($startDate, $endDate, 50);
            $eventCounts = $this->analyticsService->getEventCounts($startDate, $endDate);
            $dailyStats = $this->analyticsService->getDailyStats($days); // or $startDate, $endDate
            $userEngagement = $this->analyticsService->getUserEngagementMetrics(20);
            $performanceMetrics = $this->analyticsService->getPerformanceMetrics($startDate, $endDate);
            $recentFeedback = $this->analyticsService->getRecentFeedback(10);
            $feedbackStats = $this->analyticsService->getFeedbackStats($startDate, $endDate);
            
            $data = [
                'title' => 'Analytics Dashboard - CosmicHub Beta',
                'summary' => $summary,
                'recentEvents' => $recentEvents,
                'eventCounts' => $eventCounts,
                'dailyStats' => $dailyStats,
                'userEngagement' => $userEngagement,
                'performanceMetrics' => $performanceMetrics,
                'recentFeedback' => $recentFeedback,
                'feedbackStats' => $feedbackStats,
                'dateRange' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'days' => $days
                ]
            ];
            
            return $this->view('analytics/dashboard', $data);
        } catch (Exception $e) {
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_ERROR,
                ['error' => 'analytics_dashboard_error', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            // Consider a more user-friendly error page or message
            return $this->json(['error' => 'Error loading analytics dashboard. Please try again later.'], 500);
        }
    }
    
    /**
     * API endpoint for real-time metrics
     */
    public function api(Request $request): Response
    {
        try {
            // Check admin permissions
            if (!$this->isLoggedIn()) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }
            
            if (!$this->isAdmin()) {
                return $this->json(['error' => 'Forbidden. Admin access required.'], 403);
            }
            
            $endpoint = $request->get('endpoint', '');
            $days = (int)$request->get('days', 7);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();
            
            switch ($endpoint) {
                case 'summary':
                    return $this->json($this->analyticsService->getDashboardSummary($startDate, $endDate));
                    
                case 'events':
                    $eventType = $request->get('type');
                    $limit = (int)$request->get('limit', 100);
                    $events = $this->analyticsService->getEvents($startDate, $endDate, $eventType, $limit);
                    return $this->json($events);
                    
                case 'metrics':
                    $metricName = $request->get('metric', '');
                    if ($metricName) {
                        $metrics = $this->analyticsService->getMetric($metricName, $startDate, $endDate);
                        return $this->json($metrics);
                    } else {
                        return $this->json(['error' => 'Metric name required'], 400);
                    }
                    
                case 'trends':
                    $metricName = $request->get('metric', '');
                    if ($metricName) {
                        $trends = $this->analyticsService->getMetricTrends($metricName, $days);
                        return $this->json($trends);
                    } else {
                        return $this->json(['error' => 'Metric name required'], 400);
                    }
                    
                case 'performance':
                    $performance = $this->analyticsService->getPerformanceMetrics($startDate, $endDate);
                    // Assuming processPerformanceData is now part of AnalyticsService or data is returned ready
                    return $this->json($performance); 
                    
                case 'user_engagement':
                    $engagement = $this->analyticsService->getUserEngagementMetrics(50);
                    return $this->json($engagement);
                    
                default:
                    return $this->json(['error' => 'Invalid endpoint'], 400);
            }
        } catch (Exception $e) {
            // Log the error for debugging
            $this->logger->error('API Error in AnalyticsController: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->json(['error' => 'Internal server error. Please try again later.'], 500);
        }
    }
    
    /**
     * Track event endpoint (for JavaScript tracking)
     */
    public function track(Request $request): Response
    {
        try {
            if (!$request->isPost()) {
                return $this->json(['error' => 'Method not allowed'], 405);
            }
            
            $input = $request->getJsonData(); // Assumes getJsonData() parses JSON body
            
            if (!$input || !isset($input['event_type'])) {
                // It's good practice to log invalid attempts if they are frequent
                $this->logger->error('Invalid track request: ' . json_encode($input));
                return $this->json(['error' => 'Invalid input: event_type is required.'], 400);
            }
            
            // Track the event
            $result = $this->analyticsService->trackEvent(
                $input['event_type'],
                $input['event_data'] ?? [],
                $input['metadata'] ?? []
            );
            
            if ($result) {
                return $this->json(['success' => true]);
            } else {
                return $this->json(['error' => 'Failed to track event'], 500);
            }
        } catch (Exception $e) {
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Generate daily metrics report
     */
    public function generateDailyReport(Request $request): Response
    {
        try {
            // Check admin permissions
            if (!$this->isLoggedIn()) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }
            
            $user = $this->getCurrentUser();
            if (!$this->isAdmin($user)) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }
            
            $date = $request->get('date', date('Y-m-d'));
            
            // Generate the report
            $report = $this->metricsModel->generateDailyReport($date);
            
            // Track report generation
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_USER_ACTION,
                ['action' => 'daily_report_generated', 'date' => $date]
            );
            
            return $this->json($report);
        } catch (Exception $e) {
            return $this->json(['error' => 'Failed to generate report'], 500);
        }
    }
    
    /**
     * Export analytics data
     */
    public function export(Request $request): Response
    {
        try {
            // Check admin permissions
            if (!$this->isLoggedIn()) {
                return $this->redirect('/dashboard');
            }
            
            $user = $this->getCurrentUser();
            if (!$this->isAdmin($user)) {
                return $this->redirect('/dashboard');
            }
            
            $format = $request->get('format', 'csv');
            $type = $request->get('type', 'events');
            $days = (int)$request->get('days', 30);
            
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
                    return $this->json(['error' => 'Invalid export type'], 400);
            }
            
            if ($format === 'csv') {
                $response = $this->exportToCsv($data, $filename);
            } else {
                $response = $this->exportToJson($data, $filename);
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
            
            return $response;
        } catch (Exception $e) {
            return $this->json(['error' => 'Error exporting data'], 500);
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
    private function exportToCsv($data, $filename): Response
    {
        $output = fopen('php://temp', 'w');
        
        if (!empty($data)) {
            // Write headers
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return new Response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}.csv"
        ]);
    }
    
    /**
     * Export data to JSON
     */
    private function exportToJson($data, $filename): Response
    {
        $content = json_encode($data, JSON_PRETTY_PRINT);
        
        return new Response($content, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename={$filename}.json"
        ]);
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin($user): bool
    {
        return isset($user['is_admin']) && $user['is_admin'] == 1;
    }
}