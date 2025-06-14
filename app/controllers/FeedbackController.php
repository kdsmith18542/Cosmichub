<?php
/**
 * FeedbackController
 * 
 * Handles user feedback collection and management for Phase 3 beta testing
 */

namespace App\Controllers;

use App\Services\AnalyticsService;
use App\Services\UserService;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\FeedbackService;
use App\Models\UserFeedback;
use App\Core\Controller\Controller;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Exception;

class FeedbackController extends Controller
{
    private AnalyticsService $analyticsService;
    private UserService $userService;
    private FeedbackService $feedbackService;

    public function __construct(
        AnalyticsService $analyticsService,
        UserService $userService,
        FeedbackService $feedbackService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->analyticsService = $analyticsService;
        $this->userService = $userService;
        $this->feedbackService = $feedbackService;
        $this->logger = $logger;
    }
    
    /**
     * Display feedback form
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            // Track page view
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_PAGE_VIEW,
                ['page' => 'feedback'],
                ['page_load_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']]
            );
            
            $data = [
                'title' => 'Feedback - CosmicHub Beta',
                'feedbackTypes' => $this->feedbackService->getFeedbackTypes(), // Assuming FeedbackService has this method
                'user' => $this->userService->getCurrentUser($request) // Assuming UserService can get current user from request
            ];
            
            return $response->render('feedback/index', $data);
        } catch (Exception $e) {
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_ERROR,
                ['error' => 'feedback_page_load_error', 'message' => $e->getMessage()]
            );
            return $response->json(['error' => 'Error loading feedback page'], 500);
        }
    }
    
    /**
     * Submit feedback
     */
    public function submit(Request $request, Response $response): Response
    {
        try {
            // Only allow POST requests
            if (!$request->isPost()) {
                return $response->redirect('/feedback');
            }
            
            // Prepare feedback data
            $feedbackData = [
                'user_id' => $request->getSession('user_id'),
                'feedback_type' => sanitize_input($request->input('feedback_type', '')),
                'rating' => (int)$request->input('rating', 0),
                'subject' => sanitize_input($request->input('subject', '')),
                'message' => sanitize_input($request->input('message', '')),
                'page_url' => sanitize_input($request->input('page_url', '')),
                'browser_info' => json_encode([
                    'user_agent' => $request->server('HTTP_USER_AGENT', ''),
                    'ip_address' => $request->server('REMOTE_ADDR', '')
                ])
            ];
            
            // Validate input
            $errors = $this->validateFeedbackInput($feedbackData);
            
            if (!empty($errors)) {
                $request->setSession('feedback_errors', $errors);
                $request->setSession('feedback_data', $feedbackData);
                return $response->redirect('/feedback');
            }
            
            // Create feedback
            $feedbackId = $this->feedbackService->createFeedback($feedbackData);
            
            if ($feedbackId) {
                // Track analytics event
                $this->analyticsService->trackEvent('feedback_submitted', [
                    'feedback_type' => $feedbackData['feedback_type'],
                    'has_rating' => !empty($feedbackData['rating']),
                    'user_id' => $feedbackData['user_id']
                ]);
                
                $request->flash('feedback_success', 'Thank you for your feedback! We appreciate your input.');
            } else {
                $request->flash('feedback_error', 'Sorry, there was an error submitting your feedback. Please try again.');
            }
            
            return $response->redirect('/feedback');
            
        } catch (Exception $e) {
            $this->logger->error('Feedback submission error: ' . $e->getMessage());
            
            // Track error
            $this->analyticsService->trackEvent('feedback_error', [
                'error' => $e->getMessage(),
                'user_id' => $request->getSession('user_id')
            ]);
            
            $request->flash('feedback_error', 'Sorry, there was an error submitting your feedback. Please try again.');
            return $response->redirect('/feedback');
        }
    }
    
    /**
     * Admin feedback management
     */
    public function admin(Request $request, Response $response): Response
    {
        try {
            // Check if user is authenticated and is admin
            if (!$this->userService->isLoggedIn($request)) { // Assuming UserService has isLoggedIn method
                return $response->redirect('/login');
            }
            
            if (!$this->userService->isAdmin($request)) { // Assuming UserService has isAdmin method
                return $response->redirect('/login');
            }
            
            // Get filters from query parameters
            $filters = [
                'status' => $request->input('status', 'all'),
                'type' => $request->input('type', 'all'),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'search' => $request->input('search', '')
            ];
            
            // Remove empty filters
            $filters = array_filter($filters);
            
            // Get feedback with pagination
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $feedbackResult = $this->feedbackService->getAllFeedback($filters, $limit, $offset);
            $feedback = $feedbackResult['data'];
            // Assuming total count for pagination is also returned by the service
            // $totalFeedback = $feedbackResult['total']; 

            $statistics = $this->feedbackService->getFeedbackStatistics();
            $ratingDistribution = $this->feedbackService->getFeedbackRatingDistribution();
            $statusCounts = $this->feedbackService->getFeedbackCountByStatus();
            
            $data = [
                'title' => 'Feedback Management - CosmicHub Beta',
                'feedback' => $feedback,
                // 'totalFeedback' => $totalFeedback, // For pagination
                'statistics' => $statistics,
                'ratingDistribution' => $ratingDistribution,
                'statusCounts' => $statusCounts,
                'feedbackTypes' => $this->feedbackService->getFeedbackTypes(),
                'statusTypes' => $this->feedbackService->getFeedbackStatusTypes(), // Assuming FeedbackService has this method
                'filters' => $filters,
                'currentPage' => $page
            ];
            
            return $response->render('feedback/admin', $data);
        } catch (Exception $e) {
            $this->logger->error('Admin feedback error: ' . $e->getMessage());
            $request->flash('error', 'Error loading feedback management');
            return $response->redirect('/dashboard');
        }
    }
    
    /**
     * Update feedback status (AJAX endpoint)
     */
    public function updateStatus(Request $request, Response $response): Response
    {
        try {
            // Only allow POST requests
            if (!$request->isPost()) {
                return $response->json(['error' => 'Method not allowed'], 405);
            }
            
            // Check if user is admin
            if (!$this->userService->isLoggedIn($request)) {
                return $response->json(['error' => 'Unauthorized'], 403);
            }
            
            if (!$this->userService->isAdmin($request)) {
                return $response->json(['error' => 'Unauthorized'], 403);
            }
            
            // Get JSON input
            $input = $request->getJsonBody();
            
            if (!$input) {
                return $this->json(['error' => 'Invalid JSON input'], 400);
            }
            $feedbackId = $input['feedback_id'] ?? null;
            $status = $input['status'] ?? null;
            
            // Validate input
            if (!$feedbackId || !$status) {
                return $this->json(['error' => 'Missing feedback ID or status'], 400);
            }
            
            // Validate status
            $validStatuses = UserFeedback::getStatusTypes();
            if (!array_key_exists($status, $validStatuses)) {
                return $this->json(['error' => 'Invalid status'], 400);
            }
            
            // Update status
            $success = $this->feedbackModel->updateStatus($feedbackId, $status);
            
            if ($success) {
                // Track the status update
                $this->analyticsService->trackEvent('feedback_status_updated', [
                    'feedback_id' => $feedbackId,
                    'new_status' => $status,
                    'admin_user_id' => $user['id']
                ]);
                
                return $this->json(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                return $this->json(['error' => 'Failed to update status'], 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Status update error: ' . $e->getMessage());
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Get feedback details (AJAX endpoint)
     */
    public function details(Request $request, $id): Response
    {
        try {
            // Check if user is admin
            if (!$this->isLoggedIn()) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }
            
            $user = $this->getCurrentUser();
            if (!$this->isAdmin($user)) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }
            
            $feedback = $this->feedbackModel->getById($id);
            
            if ($feedback) {
                // Parse browser info if it exists
                if ($feedback['browser_info']) {
                    $feedback['browser_info'] = json_decode($feedback['browser_info'], true);
                }
                
                return $this->json($feedback);
            } else {
                return $this->json(['error' => 'Feedback not found'], 404);
            }
        } catch (Exception $e) {
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Quick feedback widget (for embedding in other pages)
     */
    public function widget(Request $request): Response
    {
        try {
            $data = [
                'feedbackTypes' => UserFeedback::getFeedbackTypes(),
                'currentPage' => $request->server('REQUEST_URI', '/')
            ];
            
            // Return JSON for AJAX requests
            if ($request->input('ajax')) {
                return $this->json($data);
            }
            
            // Return HTML widget
            return $this->view('feedback/widget', $data);
        } catch (Exception $e) {
            if ($request->input('ajax')) {
                return $this->json(['error' => 'Failed to load feedback widget'], 500);
            } else {
                return $this->response('<div class="alert alert-danger">Failed to load feedback widget</div>', 500);
            }
        }
    }
    
    /**
     * Validate feedback input
     */
    private function validateFeedbackInput($data)
    {
        $errors = [];
        
        // Validate feedback type
        if (empty($data['feedback_type'])) {
            $errors['feedback_type'] = 'Feedback type is required';
        } elseif (!array_key_exists($data['feedback_type'], UserFeedback::getFeedbackTypes())) {
            $errors['feedback_type'] = 'Invalid feedback type';
        }
        
        // Validate message
        if (empty($data['message'])) {
            $errors['message'] = 'Message is required';
        } elseif (strlen($data['message']) < 10) {
            $errors['message'] = 'Message must be at least 10 characters long';
        } elseif (strlen($data['message']) > 2000) {
            $errors['message'] = 'Message must be less than 2000 characters';
        }
        
        // Validate rating if provided
        if (!empty($data['rating'])) {
            $rating = (int)$data['rating'];
            if ($rating < 1 || $rating > 5) {
                $errors['rating'] = 'Rating must be between 1 and 5';
            }
        }
        
        // Validate subject if provided
        if (!empty($data['subject']) && strlen($data['subject']) > 255) {
            $errors['subject'] = 'Subject must be less than 255 characters';
        }
        
        return $errors;
    }
    
    /**
     * Check if user is admin (implement based on your user role system)
     */
    private function isAdmin($user): bool
    {
        // This is a placeholder - implement based on your user role system
        // For now, you might check for a specific user ID or email
        return isset($user['is_admin']) && $user['is_admin'] == 1;
    }
}