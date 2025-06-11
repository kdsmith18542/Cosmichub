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
        if (!\App\Helpers\Auth::is_logged_in()) {
            Session::setFlash('error', 'Please log in to access this page.');
            return redirect('/login');
        }

        $user = User::findById(Auth::get_current_user_id());

        if (!$user) {
            Session::setFlash('error', 'User not found. Please log in again.');
            Auth::logout(); // Log out if user session is invalid
            return redirect('/login');
        }

        if ($user->hasVerifiedEmail()) {
            Session::setFlash('info', 'Your email is already verified.');
            return redirect('/dashboard');
        }

        // Pass any messages from resend attempts
        $emailSentStatus = Session::getFlash('email_sent_status');
        $errorMessage = Session::getFlash('error_message');

        return view('auth/verify-email-notice', [
            'title' => 'Verify Your Email Address',
            'email' => $user->getEmailForVerification(),
            'email_sent_status' => $emailSentStatus,
            'error_message' => $errorMessage
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
        if (!is_numeric($userId)) {
            Session::setFlash('error', 'Invalid user ID format.');
            return redirect('/login');
        }

        $user = User::findById((int)$userId);

        if (!$user) {
            Session::setFlash('error', 'Invalid verification link. User not found.');
            return redirect('/login');
        }

        if ($user->hasVerifiedEmail()) {
            Session::setFlash('info', 'Your email is already verified. You can login now.');
            // If user is somehow not logged in but verifying, log them in.
            if (!Auth::is_logged_in()) {
                Auth::login($user);
            }
            return redirect('/dashboard');
        }

        $result = $user->verifyEmail($token); // $token is the raw token from URL

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
            // Ensure user is logged in after successful verification
            if (!Auth::is_logged_in()) {
                Auth::login($user);
            }
            return redirect('/dashboard');
        } else {
            Session::setFlash('error', $result['message']);
            if (isset($result['expired']) && $result['expired']) {
                return redirect('/email/verify/resend?email=' . urlencode($user->getEmailForVerification()));
            }
            return redirect('/email/verify/notice');
        }
    }
    
    /**
     * Resend the email verification notification
     */
    public function resend(Request $request = null) // Make Request optional for direct calls
    {
        $email = null;
        if ($request && $request->isPost()) {
            // CSRF check for POST requests
            if (!csrf_verify($request)) {
                Session::setFlash('error', 'Invalid request. Please try again.');
                return redirect('/email/verify/resend');
            }
            $email = $request->input('email');
        } elseif (Auth::is_logged_in()) {
            $currentUser = User::findById(Auth::get_current_user_id());
            if ($currentUser) $email = $currentUser->email;
        }

        if (empty($email)) {
            Session::setFlash('error', 'Email address is required.');
            return redirect('/email/verify/resend');
        }

        $user = User::findByEmail($email);

        if (!$user) {
            // To prevent user enumeration, show a generic message
            Session::setFlash('email_sent_status', 'success'); // Pretend success
            Session::setFlash('success', 'If an account with that email exists and requires verification, a new link has been sent.');
            return redirect('/email/verify/notice');
        }

        if ($user->hasVerifiedEmail()) {
            Session::setFlash('info', 'Your email is already verified.');
            return redirect(Auth::is_logged_in() ? '/dashboard' : '/login');
        }

        $emailService = new EmailService();
        $sendResult = $emailService->sendVerificationEmail($user);

        if ($sendResult['success']) {
            Session::setFlash('success', $sendResult['message']);
            Session::setFlash('email_sent_status', 'success');
        } else {
            Session::setFlash('error', $sendResult['message']);
            Session::setFlash('email_sent_status', 'failed');
            Session::setFlash('error_message', $sendResult['message']); // For notice page
        }
        
        return redirect('/email/verify/notice');
    }

    /**
     * Show the form to request a new verification email.
     */
    public function showResendForm(Request $request = null)
    {
        $email = $request ? $request->input('email', '') : '';
        if (empty($email) && Auth::is_logged_in()) {
            $currentUser = User::findById(Auth::get_current_user_id());
            if ($currentUser && !$currentUser->hasVerifiedEmail()) {
                $email = $currentUser->email;
            }
        }
        return view('auth/verify-email-resend-form', ['email' => $email, 'title' => 'Resend Verification Email']);
    }
}
