<?php
/**
 * UserFeedback Model
 * 
 * Handles user feedback collection for Phase 3 beta testing
 */

namespace App\Models;

use App\Libraries\Database;
use PDO;
use Exception;

class UserFeedback
{
    protected $db;
    protected $table = 'user_feedback';
    
    // Feedback types
    const TYPE_BUG_REPORT = 'bug_report';
    const TYPE_FEATURE_REQUEST = 'feature_request';
    const TYPE_GENERAL_FEEDBACK = 'general_feedback';
    const TYPE_UI_UX_FEEDBACK = 'ui_ux_feedback';
    const TYPE_PERFORMANCE_ISSUE = 'performance_issue';
    const TYPE_SUGGESTION = 'suggestion';
    
    // Status types
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new feedback
     */
    public function create($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (user_id, feedback_type, rating, subject, message, page_url, browser_info, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                $data['user_id'] ?? null,
                $data['feedback_type'],
                $data['rating'] ?? null,
                $data['subject'] ?? null,
                $data['message'],
                $data['page_url'] ?? $_SERVER['REQUEST_URI'] ?? null,
                json_encode($this->getBrowserInfo()),
                self::STATUS_PENDING,
                date('Y-m-d H:i:s')
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("User Feedback Creation Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get feedback by ID
     */
    public function getById($id)
    {
        try {
            $sql = "SELECT f.*, u.username, u.email 
                    FROM {$this->table} f
                    LEFT JOIN users u ON f.user_id = u.id
                    WHERE f.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("User Feedback Get By ID Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all feedback with filters
     */
    public function getAll($filters = [], $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT f.*, u.username, u.email 
                    FROM {$this->table} f
                    LEFT JOIN users u ON f.user_id = u.id
                    WHERE 1=1";
            $params = [];
            
            // Apply filters
            if (!empty($filters['feedback_type'])) {
                $sql .= " AND f.feedback_type = ?";
                $params[] = $filters['feedback_type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND f.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND f.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['rating_min'])) {
                $sql .= " AND f.rating >= ?";
                $params[] = $filters['rating_min'];
            }
            
            if (!empty($filters['rating_max'])) {
                $sql .= " AND f.rating <= ?";
                $params[] = $filters['rating_max'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND f.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND f.created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("User Feedback Get All Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update feedback status
     */
    public function updateStatus($id, $status)
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = ?, updated_at = ? 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, date('Y-m-d H:i:s'), $id]);
        } catch (Exception $e) {
            error_log("User Feedback Update Status Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get feedback statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null)
    {
        try {
            $sql = "SELECT 
                        feedback_type,
                        status,
                        COUNT(*) as count,
                        AVG(rating) as avg_rating
                    FROM {$this->table}
                    WHERE 1=1";
            $params = [];
            
            if ($dateFrom) {
                $sql .= " AND created_at >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND created_at <= ?";
                $params[] = $dateTo;
            }
            
            $sql .= " GROUP BY feedback_type, status
                     ORDER BY feedback_type, status";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("User Feedback Get Statistics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get rating distribution
     */
    public function getRatingDistribution($feedbackType = null)
    {
        try {
            $sql = "SELECT 
                        rating,
                        COUNT(*) as count
                    FROM {$this->table}
                    WHERE rating IS NOT NULL";
            $params = [];
            
            if ($feedbackType) {
                $sql .= " AND feedback_type = ?";
                $params[] = $feedbackType;
            }
            
            $sql .= " GROUP BY rating ORDER BY rating";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("User Feedback Get Rating Distribution Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent feedback
     */
    public function getRecent($limit = 10)
    {
        try {
            $sql = "SELECT f.*, u.username 
                    FROM {$this->table} f
                    LEFT JOIN users u ON f.user_id = u.id
                    ORDER BY f.created_at DESC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("User Feedback Get Recent Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get feedback count by status
     */
    public function getCountByStatus()
    {
        try {
            $sql = "SELECT status, COUNT(*) as count 
                    FROM {$this->table} 
                    GROUP BY status";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to associative array
            $counts = [];
            foreach ($results as $result) {
                $counts[$result['status']] = $result['count'];
            }
            
            return $counts;
        } catch (Exception $e) {
            error_log("User Feedback Get Count By Status Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete feedback
     */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("User Feedback Delete Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get browser information
     */
    private function getBrowserInfo()
    {
        return [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
            'screen_resolution' => null, // Will be filled by JavaScript
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get feedback types
     */
    public static function getFeedbackTypes()
    {
        return [
            self::TYPE_BUG_REPORT => 'Bug Report',
            self::TYPE_FEATURE_REQUEST => 'Feature Request',
            self::TYPE_GENERAL_FEEDBACK => 'General Feedback',
            self::TYPE_UI_UX_FEEDBACK => 'UI/UX Feedback',
            self::TYPE_PERFORMANCE_ISSUE => 'Performance Issue',
            self::TYPE_SUGGESTION => 'Suggestion'
        ];
    }
    
    /**
     * Get status types
     */
    public static function getStatusTypes()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed'
        ];
    }
}