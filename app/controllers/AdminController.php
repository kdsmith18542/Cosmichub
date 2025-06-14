<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\AdminService;
use Exception;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * Unified Admin Controller
 * 
 * Comprehensive admin controller combining analytics, feedback management,
 * user management, content management, and system monitoring
 */
class AdminController extends Controller {
    
    /** @var AdminService */
    private $adminService;
    private $logger;
    
    public function __construct(AdminService $adminService, LoggerInterface $logger) {
        parent::__construct();
        $this->adminService = $adminService;
        $this->logger = $logger;
    }
    
    /**
     * Check if current user is admin
     */
    private function isAdmin(): bool {
        $user = $this->getCurrentUser();
        return $this->adminService->isAdmin($user);
    }
    
    /**
     * Main admin dashboard
     */
    public function dashboard(Request $request): Response {
        try {
            if (!$this->isAdmin()) {
                return $this->json(['error' => 'Access denied. Admin privileges required.'], 403);
            }
            
            // Get date range from request or default to last 7 days
            $startDate = Carbon::parse($request->input('start_date', Carbon::now()->subDays(7)->toDateString()));
            $endDate = Carbon::parse($request->input('end_date', Carbon::now()->toDateString()));
            $days = $endDate->diffInDays($startDate) + 1;
            
            $dateRange = [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days
            ];
            
            // Gather all dashboard data using AdminService
            $data = [
                'title' => 'Admin Dashboard - CosmicHub',
                'summary' => $this->adminService->getSummaryData($startDate->toDateString(), $endDate->toDateString()),
                'recentEvents' => $this->adminService->getRecentEvents($startDate->toDateString(), $endDate->toDateString()),
                'eventCounts' => $this->adminService->getEventCounts($startDate->toDateString(), $endDate->toDateString()),
                'dailyStats' => $this->adminService->getDailyStats($startDate->toDateString(), $endDate->toDateString()),
                'userEngagement' => $this->adminService->getUserEngagement($startDate->toDateString(), $endDate->toDateString()),
                'performanceMetrics' => $this->adminService->getPerformanceMetrics($startDate->toDateString(), $endDate->toDateString()),
                'recentFeedback' => $this->adminService->getRecentFeedback(),
                'feedbackStats' => $this->adminService->getFeedbackStats(),
                'userStats' => $this->adminService->getUserStats(),
                'systemHealth' => $this->adminService->getSystemHealth(),
                'contentStats' => $this->adminService->getContentStats(),
                'dateRange' => $dateRange
            ];
            
            return $this->view('admin/dashboard', $data);
            
        } catch (Exception $e) {
            $this->logger->error("Admin Dashboard Error: " . $e->getMessage());
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Export data in various formats
     */
    public function export(Request $request): Response {
        try {
            if (!$this->isAdmin()) {
                return $this->json(['error' => 'Access denied'], 403);
            }
            
            $type = $request->input('type', 'users');
            $format = $request->input('format', 'csv');
            
            switch ($type) {
                case 'users':
                    return $this->exportUsers($format);
                case 'feedback':
                    return $this->exportFeedback($format);
                case 'analytics':
                    return $this->exportAnalytics($format);
                // case 'all': // Assuming exportAll will be implemented in AdminService
                //     return $this->adminService->exportAllData($format);
                default:
                    return $this->json(['error' => 'Invalid export type'], 400);
            }
            
        } catch (Exception $e) {
            $this->logger->error("Export Error: " . $e->getMessage());
            return $this->json(['error' => 'Export failed'], 500);
        }
    }
    
    /**
     * Export users data
     */
    private function exportUsers($format = 'csv'): Response {
        $users = $this->adminService->getUsersForExport();
        
        if ($format === 'csv') {
            $output = fopen('php://temp', 'w');
            if (!empty($users)) {
                fputcsv($output, array_keys($users[0]));
                foreach ($users as $row) {
                    fputcsv($output, $row);
                }
            }
            rewind($output);
            $content = stream_get_contents($output);
            fclose($output);
            
            return $this->response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="users_export_' . Carbon::now()->toDateString() . '.csv"'
            ]);
        }
        
        return $this->json(['error' => 'Unsupported format'], 400);
    }
    
    /**
     * Export feedback data
     */
    private function exportFeedback($format = 'csv'): Response {
        $feedback = $this->adminService->getFeedbackForExport();
        
        if ($format === 'csv') {
            $output = fopen('php://temp', 'w');
            if (!empty($feedback)) {
                fputcsv($output, array_keys($feedback[0]));
                foreach ($feedback as $row) {
                    fputcsv($output, $row);
                }
            }
            rewind($output);
            $content = stream_get_contents($output);
            fclose($output);
            
            return $this->response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="feedback_export_' . Carbon::now()->toDateString() . '.csv"'
            ]);
        }
        
        return $this->json(['error' => 'Unsupported format'], 400);
    }
    
    /**
     * Export analytics data
     */
    private function exportAnalytics($format = 'csv'): Response {
        $analytics = $this->adminService->getAnalyticsForExport(); // Assuming a 30-day default or configurable in service
        
        if ($format === 'csv') {
            $output = fopen('php://temp', 'w');
            if (!empty($analytics)) {
                fputcsv($output, array_keys($analytics[0]));
                foreach ($analytics as $row) {
                    fputcsv($output, $row);
                }
            }
            rewind($output);
            $content = stream_get_contents($output);
            fclose($output);
            
            return $this->response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="analytics_export_' . date('Y-m-d') . '.csv"'
            ]);
        }
        
        return $this->json(['error' => 'Unsupported format'], 400);
    }
    
    /**
     * Export all data
     */
    private function exportAll($format = 'csv'): Response {
        // For now, just export users as an example
        return $this->exportUsers($format);
    }
}