<?php
/**
 * Analytics Service
 * 
 * Handles user behavior tracking, feature usage analytics, and performance metrics
 * for Phase 3 beta testing and beyond
 */

namespace App\Services;

use App\Libraries\Database;
use Exception;

class AnalyticsService {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Track user events for analytics
     */
    public function trackEvent($userId, $eventType, $eventData = [], $metadata = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO analytics_events 
                (user_id, event_type, event_data, metadata, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $eventType,
                json_encode($eventData),
                json_encode($metadata),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Analytics tracking error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track page views
     */
    public function trackPageView($userId, $page, $referrer = null) {
        return $this->trackEvent($userId, 'page_view', [
            'page' => $page,
            'referrer' => $referrer ?? $_SERVER['HTTP_REFERER'] ?? null,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Track feature usage
     */
    public function trackFeatureUsage($userId, $feature, $action, $details = []) {
        return $this->trackEvent($userId, 'feature_usage', [
            'feature' => $feature,
            'action' => $action,
            'details' => $details,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Track user engagement metrics
     */
    public function trackEngagement($userId, $sessionDuration, $pagesViewed, $actionsPerformed) {
        return $this->trackEvent($userId, 'engagement', [
            'session_duration' => $sessionDuration,
            'pages_viewed' => $pagesViewed,
            'actions_performed' => $actionsPerformed,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Track conversion events
     */
    public function trackConversion($userId, $conversionType, $value = null, $details = []) {
        return $this->trackEvent($userId, 'conversion', [
            'conversion_type' => $conversionType,
            'value' => $value,
            'details' => $details,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Track errors and issues
     */
    public function trackError($userId, $errorType, $errorMessage, $context = []) {
        return $this->trackEvent($userId, 'error', [
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'context' => $context,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Get analytics dashboard data
     */
    public function getDashboardMetrics($dateRange = '7 days') {
        try {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$dateRange}"));
            
            // Get total events
            $totalEvents = $this->db->query(
                "SELECT COUNT(*) as count FROM analytics_events WHERE created_at >= ?",
                [$startDate]
            )->fetch()['count'];
            
            // Get unique users
            $uniqueUsers = $this->db->query(
                "SELECT COUNT(DISTINCT user_id) as count FROM analytics_events WHERE created_at >= ?",
                [$startDate]
            )->fetch()['count'];
            
            // Get top features
            $topFeatures = $this->db->query("
                SELECT JSON_EXTRACT(event_data, '$.feature') as feature, COUNT(*) as usage_count
                FROM analytics_events 
                WHERE event_type = 'feature_usage' AND created_at >= ?
                GROUP BY JSON_EXTRACT(event_data, '$.feature')
                ORDER BY usage_count DESC
                LIMIT 10
            ", [$startDate])->fetchAll();
            
            // Get conversion rates
            $conversions = $this->db->query(
                "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'conversion' AND created_at >= ?",
                [$startDate]
            )->fetch()['count'];
            
            // Get error rates
            $errors = $this->db->query(
                "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'error' AND created_at >= ?",
                [$startDate]
            )->fetch()['count'];
            
            return [
                'total_events' => $totalEvents,
                'unique_users' => $uniqueUsers,
                'top_features' => $topFeatures,
                'conversions' => $conversions,
                'errors' => $errors,
                'conversion_rate' => $uniqueUsers > 0 ? round(($conversions / $uniqueUsers) * 100, 2) : 0,
                'error_rate' => $totalEvents > 0 ? round(($errors / $totalEvents) * 100, 2) : 0
            ];
        } catch (Exception $e) {
            error_log('Analytics dashboard error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user behavior insights
     */
    public function getUserBehaviorInsights($userId, $dateRange = '30 days') {
        try {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$dateRange}"));
            
            // Get user's most used features
            $topFeatures = $this->db->query("
                SELECT JSON_EXTRACT(event_data, '$.feature') as feature, COUNT(*) as usage_count
                FROM analytics_events 
                WHERE user_id = ? AND event_type = 'feature_usage' AND created_at >= ?
                GROUP BY JSON_EXTRACT(event_data, '$.feature')
                ORDER BY usage_count DESC
                LIMIT 5
            ", [$userId, $startDate])->fetchAll();
            
            // Get session patterns
            $sessionData = $this->db->query("
                SELECT 
                    AVG(CAST(JSON_EXTRACT(event_data, '$.session_duration') AS DECIMAL)) as avg_session_duration,
                    AVG(CAST(JSON_EXTRACT(event_data, '$.pages_viewed') AS DECIMAL)) as avg_pages_per_session,
                    COUNT(*) as total_sessions
                FROM analytics_events 
                WHERE user_id = ? AND event_type = 'engagement' AND created_at >= ?
            ", [$userId, $startDate])->fetch();
            
            return [
                'top_features' => $topFeatures,
                'session_data' => $sessionData
            ];
        } catch (Exception $e) {
            error_log('User behavior insights error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Track A/B test participation
     */
    public function trackABTest($userId, $testName, $variant, $outcome = null) {
        return $this->trackEvent($userId, 'ab_test', [
            'test_name' => $testName,
            'variant' => $variant,
            'outcome' => $outcome,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($dateRange = '24 hours') {
        try {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$dateRange}"));
            
            // Get page load times (if tracked)
            $pageLoadTimes = $this->db->query("
                SELECT 
                    JSON_EXTRACT(metadata, '$.page_load_time') as load_time,
                    JSON_EXTRACT(event_data, '$.page') as page
                FROM analytics_events 
                WHERE event_type = 'page_view' 
                AND JSON_EXTRACT(metadata, '$.page_load_time') IS NOT NULL
                AND created_at >= ?
                ORDER BY created_at DESC
                LIMIT 100
            ", [$startDate])->fetchAll();
            
            // Calculate average load time
            $avgLoadTime = 0;
            if (!empty($pageLoadTimes)) {
                $totalTime = array_sum(array_column($pageLoadTimes, 'load_time'));
                $avgLoadTime = $totalTime / count($pageLoadTimes);
            }
            
            return [
                'avg_page_load_time' => round($avgLoadTime, 2),
                'total_page_views' => count($pageLoadTimes)
            ];
        } catch (Exception $e) {
            error_log('Performance metrics error: ' . $e->getMessage());
            return [];
        }
    }
}