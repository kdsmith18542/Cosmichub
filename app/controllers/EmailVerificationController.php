<?php

namespace App\Controllers;

use PDO;
use Exception;
use App\Libraries\Controller;
use App\Models\User;
use App\Models\UserToken;
use App\Services\EmailService;

class EmailVerificationController extends Controller
{
    /**
     * Show the email verification notice
     */
    public function notice()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->setFlash('error', 'Please log in to verify your email.');
            return $this->redirect('/login');
        }
        
        $user = User::find($_SESSION['user_id']);
        
        // Redirect if already verified
        if ($user && $user->hasVerifiedEmail()) {
            $this->setFlash('info', 'Your email has already been verified.');
            return $this->redirect('/dashboard');
        }
        
        return $this->view('auth.verify-email', [
            'title' => 'Verify Email Address',
            'email' => $user ? $user->email : null,
            'resendUrl' => '/email/verification-notification'
        ]);
    }
    
    /**
     * Verify the user's email address
     * 
     * @param int $userId
     * @param string $token
     */
    public function verify($userId, $token)
    {
        try {
            // Find the user
            $user = User::find($userId);
            
            if (!$user) {
                throw new Exception('Invalid verification link. User not found.');
            }
            
            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                $this->setFlash('success', 'Your email has already been verified.');
                return $this->redirect('/dashboard');
            }
            
            // Verify the token
            $result = $user->verifyEmail($token);
            
            if ($result['success']) {
                $this->setFlash('success', $result['message']);
                
                // Log in the user if not already logged in
                if (!isset($_SESSION['user_id'])) {
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_email'] = $user->email;
                }
                
                return $this->redirect('/dashboard');
            } else {
                throw new Exception($result['message']);
            }
            
            // This code is unreachable due to the return statement above
            // Keeping it for reference but should be removed in production
            // throw new Exception('Unexpected code execution path');
            
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
            return $this->redirect('/email/verify');
        }
    }
    
    /**
     * Resend the email verification notification
     */
    public function resend()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            return $this->json([
                'success' => false,
                'message' => 'You must be logged in to resend the verification email.'
            ], 401);
        }
        
        $user = User::find($_SESSION['user_id']);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }
        
        if ($user->hasVerifiedEmail()) {
            return $this->json([
                'success' => false,
                'message' => 'Your email is already verified.'
            ], 400);
        }
        
        try {
            // Create a new verification token
            $token = $user->createEmailVerificationToken();
            
            // Send the verification email
            $emailSent = $user->sendEmailVerificationNotification();
            
            if ($emailSent) {
                return $this->json([
                    'success' => true,
                    'message' => 'A fresh verification link has been sent to your email address.'
                ]);
            } else {
                throw new Exception('Failed to send verification email.');
            }
            
        } catch (\Exception $e) {
            error_log('Failed to resend verification email: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Unable to send verification email. Please try again later.'
            ], 500);
        }
    }
}
