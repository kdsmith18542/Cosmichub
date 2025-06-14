<?php

namespace App\Repositories;

use App\Core\Repository;
use Exception;

class AnalyticsRepository extends Repository
{
    protected $table = 'analytics_events';
    
    /**
     * Track an analytics event
     */
    public function trackEvent($userId, $eventType, $eventData = [], $metadata = [])
    {
        try {
            return $this->create([
                'user_id' => $userId,
                'event_type' => $eventType,
                'event_data' => json_encode($eventData),
                'metadata' => json_encode($metadata),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to track event: ' . $e->getMessage());
        }
    }
    
    /**
     * Get total events count since date
     */
    public function getTotalEventsCount($startDate)
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM analytics_events WHERE created_at >= ?",
            [$startDate]
        )->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Get unique users count since date
     */
    public function getUniqueUsersCount($startDate)
    {
        $result = $this->db->query(
            "SELECT COUNT(DISTINCT user_id) as count FROM analytics_events WHERE created_at >= ?",
            [$startDate]
        )->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Get top features usage
     */
    public function getTopFeatures($startDate, $limit = 10)
    {
        return $this->db->query("
            SELECT JSON_EXTRACT(event_data, '$.feature') as feature, COUNT(*) as usage_count
            FROM analytics_events 
            WHERE event_type = 'feature_usage' AND created_at >= ?
            GROUP BY JSON_EXTRACT(event_data, '$.feature')
            ORDER BY usage_count DESC
            LIMIT ?
        ", [$startDate, $limit])->fetchAll();
    }
    
    /**
     * Get conversions count since date
     */
    public function getConversionsCount($startDate)
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'conversion' AND created_at >= ?",
            [$startDate]
        )->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Get errors count since date
     */
    public function getErrorsCount($startDate)
    {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'error' AND created_at >= ?",
            [$startDate]
        )->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Get user's top features
     */
    public function getUserTopFeatures($userId, $startDate, $limit = 5)
    {
        return $this->db->query("
            SELECT JSON_EXTRACT(event_data, '$.feature') as feature, COUNT(*) as usage_count
            FROM analytics_events 
            WHERE user_id = ? AND event_type = 'feature_usage' AND created_at >= ?
            GROUP BY JSON_EXTRACT(event_data, '$.feature')
            ORDER BY usage_count DESC
            LIMIT ?
        ", [$userId, $startDate, $limit])->fetchAll();
    }
    
    /**
     * Get user session data
     */
    public function getUserSessionData($userId, $startDate)
    {
        return $this->db->query("
            SELECT 
                AVG(CAST(JSON_EXTRACT(event_data, '$.session_duration') AS DECIMAL)) as avg_session_duration,
                AVG(CAST(JSON_EXTRACT(event_data, '$.pages_viewed') AS DECIMAL)) as avg_pages_per_session,
                COUNT(*) as total_sessions
            FROM analytics_events 
            WHERE user_id = ? AND event_type = 'engagement' AND created_at >= ?
        ", [$userId, $startDate])->fetch();
    }
    
    /**
     * Track A/B test result
     */
    public function trackABTest($userId, $testName, $variant, $outcome = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ab_test_results 
                (user_id, test_name, variant, outcome, created_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $testName,
                $variant,
                $outcome,
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to track A/B test: ' . $e->getMessage());
        }
    }
    
    /**
     * Get page load times
     */
    public function getPageLoadTimes($startDate)
    {
        return $this->db->query("
            SELECT 
                AVG(CAST(JSON_EXTRACT(event_data, '$.load_time') AS DECIMAL)) as avg_load_time,
                MAX(CAST(JSON_EXTRACT(event_data, '$.load_time') AS DECIMAL)) as max_load_time,
                MIN(CAST(JSON_EXTRACT(event_data, '$.load_time') AS DECIMAL)) as min_load_time
            FROM analytics_events 
            WHERE event_type = 'page_load' AND created_at >= ?
        ", [$startDate])->fetch();
    }
    
    /**
     * Get error rates by type
     */
    public function getErrorRatesByType($startDate)
    {
        return $this->db->query("
            SELECT 
                JSON_EXTRACT(event_data, '$.error_type') as error_type,
                COUNT(*) as error_count
            FROM analytics_events 
            WHERE event_type = 'error' AND created_at >= ?
            GROUP BY JSON_EXTRACT(event_data, '$.error_type')
            ORDER BY error_count DESC
        ", [$startDate])->fetchAll();
    }
    
    /**
     * Get events by user ID
     */
    public function getEventsByUser($userId, $limit = 100)
    {
        return $this->query
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get events by type
     */
    public function getEventsByType($eventType, $limit = 100)
    {
        return $this->query
            ->where('event_type', $eventType)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get events within date range
     */
    public function getEventsByDateRange($startDate, $endDate = null)
    {
        $query = $this->query->where('created_at', '>=', $startDate);
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query->orderBy('created_at', 'DESC')->get();
    }
}