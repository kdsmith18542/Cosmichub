<?php
/**
 * Dashboard Controller
 * 
 * Handles the user dashboard and related functionality
 */

// Load the base controller class
require_once __DIR__ . '/../libraries/Controller.php';

// Load models - using direct includes since autoloading isn't working as expected
require_once __DIR__ . '/../libraries/Model.php';
require_once __DIR__ . '/../libraries/QueryBuilder.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/CreditTransaction.php';
require_once __DIR__ . '/../models/Report.php';

// External dependencies
use Carbon\Carbon;
use Exception;

// Import models with full namespace
use App\Models\User as UserModel;
use App\Models\CreditTransaction as CreditTransactionModel;
use App\Models\Report as ReportModel;

class DashboardController extends \App\Libraries\Controller {
    /**
     * @var string Default layout file
     */
    protected $layout = 'layouts/main';
    
    /**
     * @var array Data to pass to views
     */
    protected $data = [];
    /**
     * Display the user dashboard
     */
    /**
     * Display the user dashboard with comprehensive statistics and recent activity
     */
    public function index() {
        // Require authentication
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        try {
            // Get user model with related data
            $userModel = new UserModel();
            $user = $userModel->find($_SESSION['user_id']);
                
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Get recent credit transactions (last 5)
            $transactionModel = new CreditTransactionModel();
            $recentTransactions = $transactionModel->query(
                "SELECT * FROM credit_transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5",
                ['user_id' => $user->id]
            );
            
            // Get recent reports with basic details (last 3)
            $reportModel = new ReportModel();
            $recentReports = $reportModel->query(
                "SELECT id, title, created_at, is_unlocked FROM reports WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 3",
                ['user_id' => $user->id]
            );
            
            // Calculate rarity score if user has birthdate
            $rarityScore = null;
            $rarityDescription = null;
            $rarityColor = null;
            
            if (!empty($user->birthdate)) {
                // Load the RarityScoreController
                require_once __DIR__ . '/RarityScoreController.php';
                $rarityController = new RarityScoreController();
                
                // Calculate the rarity score
                $rarityScore = $rarityController->calculateRarityScore($user->birthdate);
                $rarityDescription = $rarityController->getRarityDescription($rarityScore);
                $rarityColor = $rarityController->getRarityColor($rarityScore);
            }
            
            // Prepare view data
            $stats = [
                'total_reports_created' => $this->getTotalReportsCreated($user->id),
                'total_credits_earned' => $this->getTotalCreditsEarned($user->id), // Assuming you'll implement this method
                'reports_this_month' => $this->getReportsThisMonth($user->id)
            ];

            $data = [
                'pageTitle' => 'Dashboard',
                'user' => $user,
                'recentTransactions' => $recentTransactions,
                'recentReports' => $recentReports,
                'rarityScore' => $rarityScore,
                'rarityDescription' => $rarityDescription,
                'rarityColor' => $rarityColor,
                'stats' => $stats
            ];
            
            // Load the dashboard view
            $this->view('dashboard/index', $data);
            
        } catch (Exception $e) {
            // Log the error
            error_log('Dashboard error: ' . $e->getMessage());
            
            // Show a user-friendly error message
            $data = [
                'pageTitle' => 'Error',
                'error' => 'An error occurred while loading the dashboard. Please try again later.'
            ];
            $this->view('error/500', $data);
        }
    }
    
    /**
     * Get recent transactions for the user
     * 
     * @param int $userId
     * @return array
     */
    protected function getRecentTransactions($userId) {
        try {
            $transaction = new CreditTransactionModel();
            return $transaction->query(
                "SELECT * FROM credit_transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5",
                ['user_id' => $userId]
            );
        } catch (Exception $e) {
            error_log('Error getting recent transactions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the user's current credit balance
     * 
     * @param int $userId
     * @return int
     */
    protected function getCurrentCredits($userId) {
        try {
            $transaction = new CreditTransactionModel();
            $result = $transaction->query("SELECT SUM(amount) as total FROM credit_transactions WHERE user_id = :user_id", [
                'user_id' => $userId
            ]);
            
            return !empty($result) ? (int)$result[0]->total : 0;
        } catch (Exception $e) {
            error_log('Error getting current credits: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get the total number of reports created by the user
     * 
     * @param int $userId
     * @return int
     */
    protected function getTotalReportsCreated($userId) {
        try {
            $report = new ReportModel();
            $result = $report->query("SELECT COUNT(*) as count FROM reports WHERE user_id = :user_id", [
                'user_id' => $userId
            ]);
            return !empty($result) ? (int)$result[0]->count : 0;
        } catch (Exception $e) {
            error_log('Error getting total reports: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total credits used by user
     * 
     * @param int $userId
     * @return int
     */
    protected function getTotalCreditsEarned($userId) {
        try {
            $transaction = new CreditTransactionModel();
            $result = $transaction->query(
                "SELECT SUM(amount) as total FROM credit_transactions WHERE user_id = :user_id AND type = :type",
                ['user_id' => $userId, 'type' => CreditTransactionModel::TYPE_CREDIT] // Assuming 'credit' means earned
            );
            return !empty($result) ? (int)$result[0]->total : 0;
        } catch (Exception $e) {
            error_log('Error getting total credits earned: ' . $e->getMessage());
            return 0;
        }
    }

    protected function getTotalCreditsUsed($userId) {
        try {
            $transaction = new CreditTransactionModel();
            $result = $transaction->query(
                "SELECT SUM(amount) as total FROM credit_transactions WHERE user_id = :user_id AND type = :type",
                ['user_id' => $userId, 'type' => 'debit']
            );
            return !empty($result) ? (int)$result[0]->total : 0;
        } catch (Exception $e) {
            error_log('Error getting total credits used: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get number of reports created this month
     * 
     * @param int $userId
     * @return int
     */
    protected function getReportsThisMonth($userId) {
        try {
            $now = new \DateTime();
            $report = new ReportModel();
            $result = $report->query(
                "SELECT COUNT(*) as count FROM reports WHERE user_id = :user_id AND YEAR(created_at) = :year AND MONTH(created_at) = :month",
                [
                    'user_id' => $userId,
                    'year' => $now->format('Y'),
                    'month' => $now->format('m')
                ]
            );
            return !empty($result) ? (int)$result[0]->count : 0;
        } catch (Exception $e) {
            error_log('Error getting reports this month: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get credits earned this month
     * 
     * @param int $userId
     * @return int
     */
    protected function getCreditsEarnedThisMonth($userId) {
        try {
            $now = new \DateTime();
            $transaction = new CreditTransactionModel();
            $result = $transaction->query(
                "SELECT SUM(amount) as total FROM credit_transactions WHERE user_id = :user_id AND type = :type AND YEAR(created_at) = :year AND MONTH(created_at) = :month",
                [
                    'user_id' => $userId,
                    'type' => 'credit',
                    'year' => $now->format('Y'),
                    'month' => $now->format('m')
                ]
            );
            return !empty($result) ? (int)$result[0]->total : 0;
        } catch (Exception $e) {
            error_log('Error getting credits earned this month: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate report completion rate
     * 
     * @param int $userId
     * @return float
     */
    protected function getReportCompletionRate($userId) {
        try {
            $report = new ReportModel();
            
            // Get total reports
            $totalResult = $report->query(
                "SELECT COUNT(*) as count FROM reports WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            $totalReports = !empty($totalResult) ? (int)$totalResult[0]->count : 0;
            
            if ($totalReports === 0) {
                return 0;
            }
            
            // Get completed reports
            $completedResult = $report->query(
                "SELECT COUNT(*) as count FROM reports WHERE user_id = :user_id AND status = :status",
                ['user_id' => $userId, 'status' => 'completed']
            );
            $completedReports = !empty($completedResult) ? (int)$completedResult[0]->count : 0;
            
            return round(($completedReports / $totalReports) * 100, 1);
        } catch (Exception $e) {
            error_log('Error calculating report completion rate: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get recent notifications for the user
     * 
     * @param int $userId
     * @return array
     */
    /**
     * Get recent notifications for the user
     * 
     * @param int $userId User ID
     * @return array Array of notifications
     */
    protected function getRecentNotifications($userId) {
        // Default empty notifications array
        $notifications = [];
        
        // Check if Notification model exists
        $notificationClass = 'App\\Models\\Notification';
        if (!class_exists($notificationClass)) {
            return $notifications;
        }
        
        try {
            // Try to get notifications using the model
            $notificationModel = new $notificationClass();
            
            // Check if we can query the database
            if (method_exists($notificationModel, 'query')) {
                $result = $notificationModel->query(
                    "SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC LIMIT 5",
                    ['user_id' => $userId]
                );
                
                if (is_array($result)) {
                    $notifications = $result;
                }
            }
        } catch (Exception $e) {
            // Log the error but don't break the dashboard
            error_log('Error getting notifications: ' . $e->getMessage());
        }
        
        return $notifications;
    }
}
