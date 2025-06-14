<?php
/**
 * Analytics Service
 * 
 * Handles user behavior tracking, feature usage analytics, and performance metrics
 * for Phase 3 beta testing and beyond
 */

namespace App\Services;

use App\Repositories\AnalyticsRepository;
use App\Core\Service\BaseService;
use Exception;
use Psr\Log\LoggerInterface;

class AnalyticsService extends BaseService {
    
    private $analyticsRepository;
    private $logger;
    
    public function __construct(AnalyticsRepository $analyticsRepository, LoggerInterface $logger) {
        $this->analyticsRepository = $analyticsRepository;
        $this->logger = $logger;
    }
    
    /**
     * Track user events for analytics
     */
    public function trackEvent($userId, $eventType, $eventData = [], $metadata = []) {
        try {
            return $this->analyticsRepository->trackEvent($userId, $eventType, $eventData, $metadata);
        } catch (Exception $e) {
            $this->logger->error('Analytics tracking error: ' . $e->getMessage());
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
            
            // Get metrics using repository methods
            $totalEvents = $this->analyticsRepository->getTotalEventsCount($startDate);
            $uniqueUsers = $this->analyticsRepository->getUniqueUsersCount($startDate);
            $topFeatures = $this->analyticsRepository->getTopFeatures($startDate, 10);
            $conversions = $this->analyticsRepository->getConversionsCount($startDate);
            $errors = $this->analyticsRepository->getErrorsCount($startDate);
            
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
            $this->logger->error('Analytics dashboard error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user behavior insights
     */
    public function getUserBehaviorInsights($userId, $dateRange = '30 days') {
        try {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$dateRange}"));
            
            // Get user insights using repository methods
            $topFeatures = $this->analyticsRepository->getUserTopFeatures($userId, $startDate, 5);
            $sessionData = $this->analyticsRepository->getUserSessionData($userId, $startDate);
            
            return [
                'top_features' => $topFeatures,
                'session_data' => $sessionData
            ];
        } catch (Exception $e) {
            $this->logger->error('User behavior insights error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Track A/B test results
     */
    public function trackABTest($userId, $testName, $variant, $outcome = null) {
        try {
            return $this->analyticsRepository->trackABTest($userId, $testName, $variant, $outcome);
        } catch (Exception $e) {
            $this->logger->error('A/B test tracking error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($dateRange = '24 hours') {
        try {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$dateRange}"));
            
            // Get performance metrics using repository methods
            $pageLoadTimes = $this->analyticsRepository->getPageLoadTimes($startDate);
            $errorRates = $this->analyticsRepository->getErrorRatesByType($startDate);
            
            return [
                'page_load_times' => $pageLoadTimes,
                'error_rates' => $errorRates
            ];
        } catch (Exception $e) {
            $this->logger->error('Performance metrics error: ' . $e->getMessage());
            return [];
        }
    }
}