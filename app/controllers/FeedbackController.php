<?php
/**
 * FeedbackController
 * 
 * Handles user feedback collection and management for Phase 3 beta testing
 */

namespace App\Controllers;

use App\Models\UserFeedback;
use App\Models\User;
use App\Services\AnalyticsService;

class FeedbackController extends BaseController
{
    private $feedbackModel;
    private $userModel;
    private $analyticsService;
    
    public function __construct()
    {
        parent::__construct();
        $this->feedbackModel = new UserFeedback();
        $this->userModel = new User();
        $this->analyticsService = new AnalyticsService();
    }
    
    /**
     * Display feedback form
     */
    public function index()
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
                'feedbackTypes' => UserFeedback::getFeedbackTypes(),
                'user' => $this->getAuthenticatedUser()
            ];
            
            $this->view('feedback/index', $data);
        } catch (Exception $e) {
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_ERROR,
                ['error' => 'feedback_page_load_error', 'message' => $e->getMessage()]
            );
            $this->handleError('Error loading feedback page');
        }
    }
    
    /**
     * Submit feedback
     */
    public function submit()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/feedback');
                return;
            }
            
            // Validate input
            $errors = $this->validateFeedbackInput($_POST);
            if (!empty($errors)) {
                $_SESSION['feedback_errors'] = $errors;
                $_SESSION['feedback_data'] = $_POST;
                $this->redirect('/feedback');
                return;
            }
            
            // Prepare feedback data
            $feedbackData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'feedback_type' => $_POST['feedback_type'],
                'rating' => !empty($_POST['rating']) ? (int)$_POST['rating'] : null,
                'subject' => $_POST['subject'] ?? null,
                'message' => $_POST['message'],
                'page_url' => $_POST['page_url'] ?? $_SERVER['HTTP_REFERER'] ?? null
            ];
            
            // Create feedback
            $feedbackId = $this->feedbackModel->create($feedbackData);
            
            if ($feedbackId) {
                // Track successful feedback submission
                $this->analyticsService->trackEvent(
                    AnalyticsService::EVENT_USER_ACTION,
                    [
                        'action' => 'feedback_submitted',
                        'feedback_type' => $feedbackData['feedback_type'],
                        'feedback_id' => $feedbackId
                    ]
                );
                
                $_SESSION['feedback_success'] = 'Thank you for your feedback! We appreciate your input.';
            } else {
                $_SESSION['feedback_errors'] = ['general' => 'Failed to submit feedback. Please try again.'];
            }
            
            $this->redirect('/feedback');
        } catch (Exception $e) {
            $this->analyticsService->trackEvent(
                AnalyticsService::EVENT_ERROR,
                ['error' => 'feedback_submission_error', 'message' => $e->getMessage()]
            );
            $_SESSION['feedback_errors'] = ['general' => 'An error occurred while submitting feedback.'];
            $this->redirect('/feedback');
        }
    }
    
    /**
     * Admin feedback management (for beta testing team)
     */
    public function admin()
    {
        try {
            // Check if user is admin (you may need to implement admin role checking)
            $user = $this->getAuthenticatedUser();
            if (!$user || !$this->isAdmin($user)) {
                $this->redirect('/dashboard');
                return;
            }
            
            // Get filters from query parameters
            $filters = [
                'feedback_type' => $_GET['type'] ?? null,
                'status' => $_GET['status'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            // Remove empty filters
            $filters = array_filter($filters);
            
            // Get feedback with pagination
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $feedback = $this->feedbackModel->getAll($filters, $limit, $offset);
            $statistics = $this->feedbackModel->getStatistics();
            $ratingDistribution = $this->feedbackModel->getRatingDistribution();
            $statusCounts = $this->feedbackModel->getCountByStatus();
            
            $data = [
                'title' => 'Feedback Management - CosmicHub Beta',
                'feedback' => $feedback,
                'statistics' => $statistics,
                'ratingDistribution' => $ratingDistribution,
                'statusCounts' => $statusCounts,
                'feedbackTypes' => UserFeedback::getFeedbackTypes(),
                'statusTypes' => UserFeedback::getStatusTypes(),
                'filters' => $filters,
                'currentPage' => $page
            ];
            
            $this->view('feedback/admin', $data);
        } catch (Exception $e) {
            $this->handleError('Error loading feedback management');
        }
    }
    
    /**
     * Update feedback status (AJAX)
     */
    public function updateStatus()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }
            
            // Check admin permissions
            $user = $this->getAuthenticatedUser();
            if (!$user || !$this->isAdmin($user)) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $feedbackId = $input['feedback_id'] ?? null;
            $status = $input['status'] ?? null;
            
            if (!$feedbackId || !$status) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }
            
            // Validate status
            $validStatuses = array_keys(UserFeedback::getStatusTypes());
            if (!in_array($status, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status']);
                return;
            }
            
            $result = $this->feedbackModel->updateStatus($feedbackId, $status);
            
            if ($result) {
                // Track status update
                $this->analyticsService->trackEvent(
                    AnalyticsService::EVENT_USER_ACTION,
                    [
                        'action' => 'feedback_status_updated',
                        'feedback_id' => $feedbackId,
                        'new_status' => $status
                    ]
                );
                
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update status']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    /**
     * Get feedback details (AJAX)
     */
    public function details($id)
    {
        try {
            // Check admin permissions
            $user = $this->getAuthenticatedUser();
            if (!$user || !$this->isAdmin($user)) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            $feedback = $this->feedbackModel->getById($id);
            
            if ($feedback) {
                // Parse browser info if it exists
                if ($feedback['browser_info']) {
                    $feedback['browser_info'] = json_decode($feedback['browser_info'], true);
                }
                
                header('Content-Type: application/json');
                echo json_encode($feedback);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Feedback not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    /**
     * Quick feedback widget (for embedding in other pages)
     */
    public function widget()
    {
        try {
            $data = [
                'feedbackTypes' => UserFeedback::getFeedbackTypes(),
                'currentPage' => $_SERVER['REQUEST_URI'] ?? '/'
            ];
            
            // Return JSON for AJAX requests
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode($data);
                return;
            }
            
            // Return HTML widget
            $this->view('feedback/widget', $data);
        } catch (Exception $e) {
            if (isset($_GET['ajax'])) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to load feedback widget']);
            } else {
                echo '<div class="alert alert-danger">Failed to load feedback widget</div>';
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
    private function isAdmin($user)
    {
        // This is a placeholder - implement based on your user role system
        // For now, you might check for a specific user ID or email
        return isset($user['is_admin']) && $user['is_admin'] == 1;
    }
    
    /**
     * Get authenticated user
     */
    private function getAuthenticatedUser()
    {
        if (isset($_SESSION['user_id'])) {
            return $this->userModel->findById($_SESSION['user_id']);
        }
        return null;
    }
}