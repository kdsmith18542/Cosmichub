<?php
/**
 * AnalyticsEvent Model
 * 
 * Handles analytics event data for tracking user behavior,
 * feature usage, and performance metrics during Phase 3 beta testing
 */

namespace App\Models;

use App\Libraries\Database;
use PDO;
use Exception;

class AnalyticsEvent
{
    protected $db;
    protected $table = 'analytics_events';
    
    // Event types constants
    const EVENT_PAGE_VIEW = 'page_view';
    const EVENT_FEATURE_USAGE = 'feature_usage';
    const EVENT_USER_ACTION = 'user_action';
    const EVENT_ERROR = 'error';
    const EVENT_PERFORMANCE = 'performance';
    const EVENT_CONVERSION = 'conversion';
    const EVENT_ENGAGEMENT = 'engagement';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new analytics event
     */
    public function create($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (user_id, event_type, event_data, metadata, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                $data['user_id'] ?? null,
                $data['event_type'],
                json_encode($data['event_data'] ?? []),
                json_encode($data['metadata'] ?? []),
                $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Analytics Event Creation Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get events by type
     */
    public function getByType($eventType, $limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE event_type = ? 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$eventType, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Analytics Get By Type Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get events by user
     */
    public function getByUser($userId, $limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Analytics Get By User Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get events within date range
     */
    public function getByDateRange($startDate, $endDate, $eventType = null)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE created_at BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            
            if ($eventType) {
                $sql .= " AND event_type = ?";
                $params[] = $eventType;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Analytics Get By Date Range Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get event counts by type
     */
    public function getEventCounts($startDate = null, $endDate = null)
    {
        try {
            $sql = "SELECT event_type, COUNT(*) as count 
                    FROM {$this->table}";
            $params = [];
            
            if ($startDate && $endDate) {
                $sql .= " WHERE created_at BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            }
            
            $sql .= " GROUP BY event_type ORDER BY count DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Analytics Get Event Counts Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get daily event statistics
     */
    public function getDailyStats($days = 30)
    {
        try {
            $sql = "SELECT 
                        DATE(created_at) as date,
                        event_type,
                        COUNT(*) as count
                    FROM {$this->table} 
                    WHERE created_at >= DATE('now', '-{$days} days')
                    GROUP BY DATE(created_at), event_type
                    ORDER BY date DESC, count DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Analytics Get Daily Stats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user engagement metrics
     */
    public function getUserEngagementMetrics($userId = null)
    {
        try {
            $sql = "SELECT 
                        user_id,
                        COUNT(*) as total_events,
                        COUNT(DISTINCT event_type) as unique_event_types,
                        MIN(created_at) as first_event,
                        MAX(created_at) as last_event
                    FROM {$this->table}";
            $params = [];
            
            if ($userId) {
                $sql .= " WHERE user_id = ?";
                $params[] = $userId;
            } else {
                $sql .= " WHERE user_id IS NOT NULL";
            }
            
            if (!$userId) {
                $sql .= " GROUP BY user_id";
            }
            
            $sql .= " ORDER BY total_events DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $userId ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Analytics Get User Engagement Error: " . $e->getMessage());
            return $userId ? null : [];
        }
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($startDate = null, $endDate = null)
    {
        try {
            $sql = "SELECT 
                        event_data,
                        metadata,
                        created_at
                    FROM {$this->table} 
                    WHERE event_type = ?";
            $params = [self::EVENT_PERFORMANCE];
            
            if ($startDate && $endDate) {
                $sql .= " AND created_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process performance data
            $metrics = [];
            foreach ($results as $result) {
                $eventData = json_decode($result['event_data'], true);
                $metadata = json_decode($result['metadata'], true);
                
                if (isset($metadata['execution_time'])) {
                    $metrics[] = [
                        'feature' => $eventData['feature'] ?? 'unknown',
                        'execution_time' => $metadata['execution_time'],
                        'date' => $result['created_at']
                    ];
                }
            }
            
            return $metrics;
        } catch (Exception $e) {
            error_log("Analytics Get Performance Metrics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old analytics data
     */
    public function cleanOldData($daysToKeep = 90)
    {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE created_at < DATE('now', '-{$daysToKeep} days')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute();
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Analytics Clean Old Data Error: " . $e->getMessage());
            return false;
        }
    }
}