<?php
/**
 * Dashboard Controller
 * 
 * Handles the user dashboard and related functionality
 */

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\CreditService;
use App\Services\ReportService;
use App\Services\UserService;
use App\Services\RarityScoreService;
use Carbon\Carbon;
use Exception;
use Psr\Log\LoggerInterface;

class DashboardController extends Controller {
    /**
     * @var string Default layout file
     */
    protected $layout = 'layouts/main';
    
    /**
     * @var array Data to pass to views
     */
    protected $data = [];
    
    /**
     * @var CreditService
     */
    protected $creditService;
    
    /**
     * @var ReportService
     */
    protected $reportService;
    
    /**
     * @var UserService
     */
    protected $userService;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * Constructor
     */
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->creditService = $this->resolve(CreditService::class);
        $this->reportService = $this->resolve(ReportService::class);
        $this->userService = $this->resolve(UserService::class);
    }
    /**
     * Display the user dashboard with comprehensive statistics and recent activity
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response {
        // Require authentication
        if (!$this->isLoggedIn()) {
            return $this->redirect('/login');
        }
        
        try {
            // Get current user
            $user = $this->getCurrentUser();
                
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Get recent credit transactions (last 5)
            $recentTransactionsResult = $this->creditService->getRecentTransactions($user->id, 5);
            $recentTransactions = $recentTransactionsResult['success'] ? $recentTransactionsResult['data']['transactions'] : [];
            
            // Get recent reports with basic details (last 3)
            $recentReportsResult = $this->reportService->getUserReports($user->id, 3);
            $recentReports = $recentReportsResult['success'] ? $recentReportsResult['data']['reports'] : [];
            
            // Calculate rarity score if user has birthdate
            $rarityScore = null;
            $rarityDescription = null;
            $rarityColor = null;
            
            if (!empty($user->birthdate)) {
                $rarityScoreService = $this->resolve(RarityScoreService::class);
                
                // Calculate the rarity score
                $rarityResult = $rarityScoreService->calculateRarityScore($user->birthdate);
                 $rarityScore = $rarityResult['score'];
                 $rarityDescription = $rarityResult['description'];
                 $rarityColor = $rarityResult['color'];
            }
            
            // Prepare view data using services
            $totalReportsResult = $this->reportService->getTotalReportsCount($user->id);
            $totalCreditsResult = $this->creditService->getTotalCreditsEarned($user->id);
            $reportsThisMonthResult = $this->reportService->getReportsThisMonth($user->id);
            
            $stats = [
                'total_reports_created' => $totalReportsResult['success'] ? $totalReportsResult['data']['count'] : 0,
                'total_credits_earned' => $totalCreditsResult['success'] ? $totalCreditsResult['data']['total'] : 0,
                'reports_this_month' => $reportsThisMonthResult['success'] ? $reportsThisMonthResult['data']['count'] : 0
            ];

            $data = [
                'title' => 'Dashboard',
                'user' => $user,
                'recentTransactions' => $recentTransactions,
                'recentReports' => $recentReports,
                'rarityScore' => $rarityScore,
                'rarityDescription' => $rarityDescription,
                'rarityColor' => $rarityColor,
                'stats' => $stats
            ];
            
            // Load the dashboard view
            return $this->view('dashboard/index', $data);
            
        } catch (Exception $e) {
            // Log the error
            $this->logger->error('Dashboard error: ' . $e->getMessage());
            
            // Show a user-friendly error message
            $data = [
                'title' => 'Error',
                'error' => 'An error occurred while loading the dashboard. Please try again later.'
            ];
            return $this->view('error/500', $data);
        }
    }
    
    /**
     * Get the user's current credit balance using service
     * 
     * @param int $userId
     * @return int
     */
    protected function getCurrentCredits($userId) {
        try {
            $result = $this->creditService->getUserBalance($userId);
            return $result['success'] ? $result['data']['balance'] : 0;
        } catch (Exception $e) {
            $this->logger->error('Error getting current credits: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get recent notifications for the user
     * 
     * @param int $userId User ID
     * @return array Array of notifications
     */
    protected function getRecentNotifications($userId) {
        try {
            // Check if Notification model exists
            if (!class_exists('App\\Models\\Notification')) {
                return [];
            }
            
            $notificationClass = 'App\\Models\\Notification';
            return $notificationClass::where('user_id', $userId)
                ->where('is_read', 0)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } catch (Exception $e) {
            // Log the error but don't break the dashboard
            $this->logger->error('Error getting notifications: ' . $e->getMessage());
            return [];
        }
    }
}
