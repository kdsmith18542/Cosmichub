<?php
/**
 * Verification Controller
 * 
 * Handles email verification and related functionality
 */

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Services\UserService;
use App\Services\EmailService;
use App\Services\UserTokenService;
use App\Core\Request;
use App\Core\Response;
use Exception;
use Psr\Log\LoggerInterface;

class VerificationController extends Controller {
    /** @var UserService */
    private $userService;
    
    /** @var EmailService */
    private $emailService;
    
    /** @var UserTokenService */
    private $userTokenService;
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(LoggerInterface $logger) {
        parent::__construct();
        $this->userService = $this->resolve(UserService::class);
        $this->emailService = $this->resolve(EmailService::class);
        $this->userTokenService = $this->resolve(UserTokenService::class);
        $this->logger = $logger;
    }
    
    /**
     * Show the verification notice page
     */
    public function notice(Request $request): Response {
        // Redirect if not logged in
        $userId = $request->getSession('user_id');
        if (!$userId) {
            $request->flash('error', 'Please log in to access this page.');
            return $this->redirect('/login');
        }
        
        // Get current user
        $user = $this->userService->getUserById($userId);
        
        // Redirect if already verified
        if ($user && $user->hasVerifiedEmail()) {
            return $this->redirect('/dashboard');
        }
        
        $data = [
            'title' => 'Verify Your Email Address',
            'email' => $user ? $user->email : '',
            'resendUrl' => '/email/verification-notification'
        ];
        
        return $this->view('auth/verify-email', $data);
    }
    
    /**
     * Verify the user's email
     * 
     * @param string $id User ID
     * @param string $token Verification token
     */
    public function verify(Request $request, $id, $token): Response {
        // Find the user
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            $request->flash('error', 'Invalid verification link.');
            return $this->redirect('/login');
        }
        
        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            $request->flash('info', 'Your email has already been verified.');
            return $this->redirect('/dashboard');
        }
        
        // Verify the token
        if ($user->verifyEmail($token)) {
            $request->flash('success', 'Your email has been verified successfully!');
            return $this->redirect('/dashboard');
        } else {
            $request->flash('error', 'Invalid or expired verification link.');
            return $this->redirect('/email/verify');
        }
    }
    
    /**
     * Resend the verification email
     */
    public function resend(Request $request): Response {
        // Require authentication
        $userId = $request->getSession('user_id');
        if (!$userId) {
            $request->flash('error', 'Please log in to access this page.');
            return $this->redirect('/login');
        }
        
        // Get current user
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            $request->flash('error', 'User not found.');
            return $this->redirect('/login');
        }
        
        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            $request->flash('info', 'Your email is already verified.');
            return $this->redirect('/dashboard');
        }
        
        try {
            // Generate and save new verification token
            $token = $this->userTokenService->createEmailVerificationToken(
                $user->id,
                bin2hex(random_bytes(32)),
                new \DateTime('+1 day')
            );
            
            // Build verification URL
            $verificationUrl = url("/email/verify/{$user->id}/{$token}");
            
            // Send verification email
            $this->mailService->sendVerificationEmail(
                $user->email,
                $user->name,
                $verificationUrl
            );
            
            $request->flash('success', 'A fresh verification link has been sent to your email address.');
            
        } catch (Exception $e) {
            $this->logger->error('Failed to send verification email: ' . $e->getMessage());
            $request->flash('error', 'Failed to send verification email. Please try again later.');
        }
        
        return $this->redirect('/email/verify');
    }
}
