<?php

namespace App\Services;

use App\Core\Service\BaseService;
use App\Models\User;
use App\Models\Report;
use App\Models\CreditTransaction;
use App\Models\Feedback;
use App\Libraries\Database;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Admin Service
 * 
 * Handles admin dashboard analytics, user management, and system monitoring
 */
class AdminService extends BaseService
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get summary statistics for admin dashboard
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getSummaryData($startDate, $endDate): array
    {
        try {
            $summary = [];
            $db = $this->getDatabaseConnection();
            
            // Total users
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $summary['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // New users today
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
            $stmt->execute();
            $summary['new_users_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total reports (if celebrity_reports table exists)
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM celebrity_reports");
                $stmt->execute();
                $summary['total_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (Exception $e) {
                $summary['total_reports'] = 0;
            }
            
            return $summary;
        } catch (Exception $e) {
            $this->logger->error('AdminService::getSummaryData error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get database connection from container
     *
     * @return \PDO
     */
    protected function getDatabaseConnection()
    {
        $app = \App\Core\Application::getInstance();
        $dbManager = $app->getContainer()->get(\App\Core\Database\DatabaseManager::class);
        return $dbManager->getConnection();
            }
            
            // Reports today
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM celebrity_reports WHERE DATE(created_at) = CURDATE()");
                $stmt->execute();
                $summary['reports_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (Exception $e) {
                $summary['reports_today'] = 0;
            }
            
            // Total credits earned
            try {
                $stmt = $db->prepare("SELECT SUM(amount) as total FROM credit_transactions WHERE type = 'earned'");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $summary['total_credits_earned'] = $result['total'] ?? 0;
            } catch (Exception $e) {
                $summary['total_credits_earned'] = 0;
            }
            
            // Credits earned today
            try {
                $stmt = $db->prepare("SELECT SUM(amount) as total FROM credit_transactions WHERE type = 'earned' AND DATE(created_at) = CURDATE()");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $summary['credits_earned_today'] = $result['total'] ?? 0;
            } catch (Exception $e) {
                $summary['credits_earned_today'] = 0;
            }
            
            return $summary;
            
        } catch (Exception $e) {
            $this->logError('Failed to get summary data', $e);
            return [];
        }
    }
    
    /**
     * Get recent events for admin dashboard
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getRecentEvents($startDate, $endDate): array
    {
        try {
            $events = [];
            $db = $this->getDatabaseConnection();
            
            // Recent user registrations
            $stmt = $db->prepare("
                SELECT 'user_registration' as event_type, 
                       username as description, 
                       created_at as event_time
                FROM users 
                WHERE created_at BETWEEN ? AND ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $userEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent reports
            try {
                $stmt = $db->prepare("
                    SELECT 'report_created' as event_type,
                           CONCAT('Report for ', celebrity_name) as description,
                           created_at as event_time
                    FROM celebrity_reports 
                    WHERE created_at BETWEEN ? AND ?
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                $stmt->execute([$startDate, $endDate . ' 23:59:59']);
                $reportEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $events = array_merge($events, $reportEvents);
            } catch (Exception $e) {
                // Table might not exist
            }
            
            // Recent credit transactions
            try {
                $stmt = $db->prepare("
                    SELECT 'credit_transaction' as event_type,
                           CONCAT(amount, ' credits ', type) as description,
                           created_at as event_time
                    FROM credit_transactions 
                    WHERE created_at BETWEEN ? AND ?
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                $stmt->execute([$startDate, $endDate . ' 23:59:59']);
                $creditEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $events = array_merge($events, $creditEvents);
            } catch (Exception $e) {
                // Table might not exist
            }
            
            $events = array_merge($events, $userEvents);
            
            // Sort by event_time descending
            usort($events, function($a, $b) {
                return strtotime($b['event_time']) - strtotime($a['event_time']);
            });
            
            return array_slice($events, 0, 20);
            
        } catch (Exception $e) {
            $this->logError('Failed to get recent events', $e);
            return [];
        }
    }
    
    /**
     * Get event counts for admin dashboard
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getEventCounts($startDate, $endDate): array
    {
        try {
            $counts = [];
            $db = $this->getDatabaseConnection();
            
            // User registrations
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN ? AND ?");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $counts['user_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Reports created
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM celebrity_reports WHERE created_at BETWEEN ? AND ?");
                $stmt->execute([$startDate, $endDate . ' 23:59:59']);
                $counts['reports_created'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                $counts['reports_created'] = 0;
            }
            
            // Credit transactions
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM credit_transactions WHERE created_at BETWEEN ? AND ?");
                $stmt->execute([$startDate, $endDate . ' 23:59:59']);
                $counts['credit_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                $counts['credit_transactions'] = 0;
            }
            
            return $counts;
            
        } catch (Exception $e) {
            $this->logError('Failed to get event counts', $e);
            return [];
        }
    }
    
    /**
     * Get daily statistics for charts
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDailyStats($startDate, $endDate): array
    {
        try {
            $stats = [];
            $db = $this->getDatabaseConnection();
            
            // Daily user registrations
            $stmt = $db->prepare("
                SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM users 
                WHERE created_at BETWEEN ? AND ? 
                GROUP BY DATE(created_at) 
                ORDER BY date
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $stats['daily_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Daily reports
            try {
                $stmt = $db->prepare("
                    SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM celebrity_reports 
                    WHERE created_at BETWEEN ? AND ? 
                    GROUP BY DATE(created_at) 
                    ORDER BY date
                ");
                $stmt->execute([$startDate, $endDate . ' 23:59:59']);
                $stats['daily_reports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stats['daily_reports'] = [];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logError('Failed to get daily stats', $e);
            return [];
        }
    }
    
    /**
     * Get user engagement metrics
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getUserEngagement($startDate, $endDate): array
    {
        try {
            $engagement = [];
            $db = $this->getDatabaseConnection();
            
            // Active users (users who performed any action)
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT user_id) as count 
                FROM credit_transactions 
                WHERE created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $engagement['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Average credits per user
            $stmt = $db->prepare("
                SELECT AVG(total_credits) as avg_credits 
                FROM (
                    SELECT user_id, SUM(amount) as total_credits 
                    FROM credit_transactions 
                    WHERE type = 'earned' AND created_at BETWEEN ? AND ?
                    GROUP BY user_id
                ) as user_credits
            ");
            $stmt->execute([$startDate, $endDate . ' 23:59:59']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $engagement['avg_credits_per_user'] = round($result['avg_credits'] ?? 0, 2);
            
            return $engagement;
            
        } catch (Exception $e) {
            $this->logError('Failed to get user engagement', $e);
            return [];
        }
    }
    
    /**
     * Get performance metrics
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getPerformanceMetrics($startDate, $endDate): array
    {
        try {
            $metrics = [];
            $db = $this->getDatabaseConnection();
            
            // Average response time (placeholder - would need actual logging)
            $metrics['avg_response_time'] = '150ms';
            
            // Error rate (placeholder - would need error logging)
            $metrics['error_rate'] = '0.5%';
            
            // Database size
            try {
                $stmt = $db->prepare("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $metrics['database_size'] = ($result['size_mb'] ?? 0) . ' MB';
            } catch (Exception $e) {
                $metrics['database_size'] = 'Unknown';
            }
            
            return $metrics;
            
        } catch (Exception $e) {
            $this->logError('Failed to get performance metrics', $e);
            return [];
        }
    }
    
    /**
     * Get recent feedback
     * 
     * @return array
     */
    public function getRecentFeedback(): array
    {
        try {
            $db = $this->getDatabaseConnection();
            
            $stmt = $db->prepare("
                SELECT f.*, u.username 
                FROM feedback f 
                LEFT JOIN users u ON f.user_id = u.id 
                ORDER BY f.created_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError('Failed to get recent feedback', $e);
            return [];
        }
    }
    
    /**
     * Get feedback statistics
     * 
     * @return array
     */
    public function getFeedbackStats(): array
    {
        try {
            $stats = [];
            $db = $this->getDatabaseConnection();
            
            // Total feedback count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM feedback");
            $stmt->execute();
            $stats['total_feedback'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Feedback by rating
            $stmt = $db->prepare("
                SELECT rating, COUNT(*) as count 
                FROM feedback 
                WHERE rating IS NOT NULL 
                GROUP BY rating 
                ORDER BY rating
            ");
            $stmt->execute();
            $stats['by_rating'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Average rating
            $stmt = $db->prepare("SELECT AVG(rating) as avg_rating FROM feedback WHERE rating IS NOT NULL");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_rating'] = round($result['avg_rating'] ?? 0, 2);
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logError('Failed to get feedback stats', $e);
            return [];
        }
    }
    
    /**
     * Get user statistics
     * 
     * @return array
     */
    public function getUserStats(): array
    {
        try {
            $stats = [];
            $db = $this->getDatabaseConnection();
            
            // Total users
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Verified users
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE email_verified_at IS NOT NULL");
            $stmt->execute();
            $stats['verified_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Admin users
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE is_admin = 1");
            $stmt->execute();
            $stats['admin_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Users registered this month
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $stmt->execute();
            $stats['users_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logError('Failed to get user stats', $e);
            return [];
        }
    }
    
    /**
     * Get system health metrics
     * 
     * @return array
     */
    public function getSystemHealth(): array
    {
        try {
            $health = [];
            
            // Database connection
            try {
                $db = $this->getDatabaseConnection();
                $stmt = $db->prepare("SELECT 1");
                $stmt->execute();
                $health['database'] = 'Connected';
            } catch (Exception $e) {
                $health['database'] = 'Error: ' . $e->getMessage();
            }
            
            // PHP version
            $health['php_version'] = PHP_VERSION;
            
            // Memory usage
            $health['memory_usage'] = round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
            
            // Disk space (if possible)
            try {
                $bytes = disk_free_space(".");
                $health['disk_space'] = round($bytes / 1024 / 1024 / 1024, 2) . ' GB free';
            } catch (Exception $e) {
                $health['disk_space'] = 'Unknown';
            }
            
            return $health;
            
        } catch (Exception $e) {
            $this->logError('Failed to get system health', $e);
            return [];
        }
    }
    
    /**
     * Get content statistics
     * 
     * @return array
     */
    public function getContentStats(): array
    {
        try {
            $stats = [];
            $db = $this->getDatabaseConnection();
            
            // Total reports
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM celebrity_reports");
                $stmt->execute();
                $stats['total_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Reports by status
                $stmt = $db->prepare("
                    SELECT status, COUNT(*) as count 
                    FROM celebrity_reports 
                    GROUP BY status
                ");
                $stmt->execute();
                $stats['reports_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stats['total_reports'] = 0;
                $stats['reports_by_status'] = [];
            }
            
            // Total credit transactions
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM credit_transactions");
                $stmt->execute();
                $stats['total_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (Exception $e) {
                $stats['total_transactions'] = 0;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logError('Failed to get content stats', $e);
            return [];
        }
    }
    
    /**
     * Check if user is admin
     * 
     * @param array $user
     * @return bool
     */
    public function isAdmin($user): bool
    {
        return $user && isset($user['is_admin']) && $user['is_admin'] == 1;
    }
}