<?php
/**
 * BetaTestMetrics Model
 * 
 * Handles beta testing metrics and KPIs for Phase 3 monitoring
 */

namespace App\Models;

use App\Libraries\Database;
use PDO;
use Exception;

class BetaTestMetrics
{
    protected $db;
    protected $table = 'beta_test_metrics';
    
    // Metric types
    const METRIC_DAILY_ACTIVE_USERS = 'daily_active_users';
    const METRIC_FEATURE_ADOPTION = 'feature_adoption';
    const METRIC_USER_RETENTION = 'user_retention';
    const METRIC_ERROR_RATE = 'error_rate';
    const METRIC_PERFORMANCE_SCORE = 'performance_score';
    const METRIC_USER_SATISFACTION = 'user_satisfaction';
    const METRIC_CONVERSION_RATE = 'conversion_rate';
    const METRIC_SESSION_DURATION = 'session_duration';
    const METRIC_PAGE_LOAD_TIME = 'page_load_time';
    const METRIC_BOUNCE_RATE = 'bounce_rate';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Record a metric
     */
    public function recordMetric($metricName, $metricValue, $metricData = null, $date = null)
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (metric_name, metric_value, metric_data, date_recorded, created_at) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                $metricName,
                $metricValue,
                $metricData ? json_encode($metricData) : null,
                $date ?? date('Y-m-d'),
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Beta Test Metrics Record Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get metric by name and date range
     */
    public function getMetric($metricName, $startDate = null, $endDate = null)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE metric_name = ?";
            $params = [$metricName];
            
            if ($startDate) {
                $sql .= " AND date_recorded >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND date_recorded <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY date_recorded DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Beta Test Metrics Get Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get latest metric value
     */
    public function getLatestMetric($metricName)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE metric_name = ? 
                    ORDER BY date_recorded DESC, created_at DESC 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$metricName]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Beta Test Metrics Get Latest Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all metrics for a specific date
     */
    public function getMetricsForDate($date)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE date_recorded = ? 
                    ORDER BY metric_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Beta Test Metrics Get For Date Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get metric trends (comparison with previous period)
     */
    public function getMetricTrends($metricName, $days = 7)
    {
        try {
            $sql = "SELECT 
                        date_recorded,
                        AVG(metric_value) as avg_value,
                        MIN(metric_value) as min_value,
                        MAX(metric_value) as max_value,
                        COUNT(*) as count
                    FROM {$this->table} 
                    WHERE metric_name = ? 
                    AND date_recorded >= DATE('now', '-{$days} days')
                    GROUP BY date_recorded
                    ORDER BY date_recorded";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$metricName]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate trends
            $trends = [];
            for ($i = 1; $i < count($results); $i++) {
                $current = $results[$i]['avg_value'];
                $previous = $results[$i-1]['avg_value'];
                $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                
                $trends[] = [
                    'date' => $results[$i]['date_recorded'],
                    'value' => $current,
                    'change_percent' => round($change, 2),
                    'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
                ];
            }
            
            return $trends;
        } catch (Exception $e) {
            error_log("Beta Test Metrics Get Trends Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate daily active users
     */
    public function calculateDailyActiveUsers($date = null)
    {
        try {
            $date = $date ?? date('Y-m-d');
            
            // Count unique users who performed any action on the given date
            $sql = "SELECT COUNT(DISTINCT user_id) as dau
                    FROM analytics_events 
                    WHERE DATE(created_at) = ? 
                    AND user_id IS NOT NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $dau = $result['dau'] ?? 0;
            
            // Record the metric
            $this->recordMetric(self::METRIC_DAILY_ACTIVE_USERS, $dau, null, $date);
            
            return $dau;
        } catch (Exception $e) {
            error_log("Beta Test Metrics Calculate DAU Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate feature adoption rate
     */
    public function calculateFeatureAdoption($feature, $date = null)
    {
        try {
            $date = $date ?? date('Y-m-d');
            
            // Get total active users
            $totalUsersSQL = "SELECT COUNT(DISTINCT user_id) as total
                             FROM analytics_events 
                             WHERE DATE(created_at) = ? 
                             AND user_id IS NOT NULL";
            
            $stmt = $this->db->prepare($totalUsersSQL);
            $stmt->execute([$date]);
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            if ($totalUsers == 0) return 0;
            
            // Get users who used the specific feature
            $featureUsersSQL = "SELECT COUNT(DISTINCT user_id) as feature_users
                               FROM analytics_events 
                               WHERE DATE(created_at) = ? 
                               AND user_id IS NOT NULL
                               AND event_type = 'feature_usage'
                               AND JSON_EXTRACT(event_data, '$.feature') = ?";
            
            $stmt = $this->db->prepare($featureUsersSQL);
            $stmt->execute([$date, $feature]);
            $featureUsers = $stmt->fetch(PDO::FETCH_ASSOC)['feature_users'] ?? 0;
            
            $adoptionRate = ($featureUsers / $totalUsers) * 100;
            
            // Record the metric
            $this->recordMetric(
                self::METRIC_FEATURE_ADOPTION, 
                $adoptionRate, 
                ['feature' => $feature, 'total_users' => $totalUsers, 'feature_users' => $featureUsers], 
                $date
            );
            
            return $adoptionRate;
        } catch (Exception $e) {
            error_log("Beta Test Metrics Calculate Feature Adoption Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate error rate
     */
    public function calculateErrorRate($date = null)
    {
        try {
            $date = $date ?? date('Y-m-d');
            
            // Get total events
            $totalEventsSQL = "SELECT COUNT(*) as total FROM analytics_events WHERE DATE(created_at) = ?";
            $stmt = $this->db->prepare($totalEventsSQL);
            $stmt->execute([$date]);
            $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            if ($totalEvents == 0) return 0;
            
            // Get error events
            $errorEventsSQL = "SELECT COUNT(*) as errors FROM analytics_events 
                              WHERE DATE(created_at) = ? AND event_type = 'error'";
            $stmt = $this->db->prepare($errorEventsSQL);
            $stmt->execute([$date]);
            $errorEvents = $stmt->fetch(PDO::FETCH_ASSOC)['errors'] ?? 0;
            
            $errorRate = ($errorEvents / $totalEvents) * 100;
            
            // Record the metric
            $this->recordMetric(
                self::METRIC_ERROR_RATE, 
                $errorRate, 
                ['total_events' => $totalEvents, 'error_events' => $errorEvents], 
                $date
            );
            
            return $errorRate;
        } catch (Exception $e) {
            error_log("Beta Test Metrics Calculate Error Rate Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate user satisfaction from feedback
     */
    public function calculateUserSatisfaction($date = null)
    {
        try {
            $date = $date ?? date('Y-m-d');
            
            $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_feedback
                    FROM user_feedback 
                    WHERE DATE(created_at) = ? 
                    AND rating IS NOT NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $satisfaction = $result['avg_rating'] ?? 0;
            $totalFeedback = $result['total_feedback'] ?? 0;
            
            // Record the metric
            $this->recordMetric(
                self::METRIC_USER_SATISFACTION, 
                $satisfaction, 
                ['total_feedback' => $totalFeedback], 
                $date
            );
            
            return $satisfaction;
        } catch (Exception $e) {
            error_log("Beta Test Metrics Calculate User Satisfaction Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get dashboard summary
     */
    public function getDashboardSummary($days = 7)
    {
        try {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            
            $summary = [];
            
            // Get latest values for key metrics
            $keyMetrics = [
                self::METRIC_DAILY_ACTIVE_USERS,
                self::METRIC_ERROR_RATE,
                self::METRIC_USER_SATISFACTION,
                self::METRIC_PERFORMANCE_SCORE
            ];
            
            foreach ($keyMetrics as $metric) {
                $latest = $this->getLatestMetric($metric);
                $summary[$metric] = [
                    'current_value' => $latest['metric_value'] ?? 0,
                    'date' => $latest['date_recorded'] ?? $endDate
                ];
            }
            
            // Get trends
            foreach ($keyMetrics as $metric) {
                $trends = $this->getMetricTrends($metric, $days);
                $summary[$metric]['trend'] = end($trends)['trend'] ?? 'stable';
                $summary[$metric]['change_percent'] = end($trends)['change_percent'] ?? 0;
            }
            
            return $summary;
        } catch (Exception $e) {
            error_log("Beta Test Metrics Get Dashboard Summary Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate daily metrics report
     */
    public function generateDailyReport($date = null)
    {
        $date = $date ?? date('Y-m-d');
        
        // Calculate all daily metrics
        $dau = $this->calculateDailyActiveUsers($date);
        $errorRate = $this->calculateErrorRate($date);
        $satisfaction = $this->calculateUserSatisfaction($date);
        
        // Calculate feature adoption for key features
        $features = ['rarity_score', 'compatibility', 'daily_vibe', 'shareables'];
        foreach ($features as $feature) {
            $this->calculateFeatureAdoption($feature, $date);
        }
        
        return [
            'date' => $date,
            'daily_active_users' => $dau,
            'error_rate' => $errorRate,
            'user_satisfaction' => $satisfaction
        ];
    }
    
    /**
     * Clean old metrics data
     */
    public function cleanOldData($daysToKeep = 365)
    {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE date_recorded < DATE('now', '-{$daysToKeep} days')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute();
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Beta Test Metrics Clean Old Data Error: " . $e->getMessage());
            return false;
        }
    }
}