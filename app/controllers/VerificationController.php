<?php
/**
 * Verification Controller
 * 
 * Handles email verification and related functionality
 */

namespace App\Controllers;

use App\Libraries\Controller;
use App\Models\User;
use App\Libraries\MailService;
use Exception;

class VerificationController extends Controller {
    /** @var User */
    private $userModel;
    
    /** @var MailService */
    private $mailService;
    
    public function __construct() {
        $this->userModel = new User();
        $this->mailService = new MailService();
    }
    
    /**
     * Show the verification notice page
     */
    public function notice() {
        // Redirect if not logged in
        $this->requireAuth('/login');
        
        // Get current user
        $userId = $_SESSION['user_id'] ?? null;
        $user = $userId ? $this->userModel->findById($userId) : null;
        
        // Redirect if already verified
        if ($user && $user->hasVerifiedEmail()) {
            $this->redirect('/dashboard');
            return;
        }
        
        $data = [
            'title' => 'Verify Your Email Address',
            'email' => $user ? $user->email : '',
            'resendUrl' => '/email/verification-notification'
        ];
        
        $this->view('auth/verify-email', $data);
    }
    
    /**
     * Verify the user's email
     * 
     * @param string $id User ID
     * @param string $token Verification token
     */
    public function verify($id, $token) {
        // Find the user
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            $this->setFlash('error', 'Invalid verification link.');
            $this->redirect('/login');
            return;
        }
        
        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            $this->setFlash('info', 'Your email has already been verified.');
            $this->redirect('/dashboard');
            return;
        }
        
        // Verify the token
        if ($user->verifyEmail($token)) {
            $this->setFlash('success', 'Your email has been verified successfully!');
            $this->redirect('/dashboard');
        } else {
            $this->setFlash('error', 'Invalid or expired verification link.');
            $this->redirect('/email/verify');
        }
    }
    
    /**
     * Resend the verification email
     */
    public function resend() {
        // Require authentication
        $this->requireAuth('/login');
        
        // Get current user
        $userId = $_SESSION['user_id'] ?? null;
        $user = $userId ? $this->userModel->findById($userId) : null;
        
        if (!$user) {
            $this->setFlash('error', 'User not found.');
            $this->redirect('/login');
            return;
        }
        
        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            $this->setFlash('info', 'Your email is already verified.');
            $this->redirect('/dashboard');
            return;
        }
        
        try {
            // Generate and save new verification token
            $token = $user->createEmailVerificationToken();
            
            // Build verification URL
            $verificationUrl = url("/email/verify/{$user->id}/{$token}");
            
            // Send verification email
            $this->mailService->sendVerificationEmail(
                $user->email,
                $user->name,
                $verificationUrl
            );
            
            $this->setFlash('success', 'A fresh verification link has been sent to your email address.');
            
        } catch (Exception $e) {
            error_log('Failed to send verification email: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to send verification email. Please try again later.');
        }
        
        $this->redirect('/email/verify');
    }
}
