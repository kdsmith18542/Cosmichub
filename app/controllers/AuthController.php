<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication (login, registration, logout, etc.)
 */

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\UserService;
use App\Services\EmailService;
use App\Utils\TokenManager;
use App\Exceptions\ValidationException;
use Exception;
use Psr\Log\LoggerInterface;

class AuthController extends Controller {
    protected UserService $userService;
    protected EmailService $emailService;
    protected TokenManager $tokenManager;

    protected LoggerInterface $logger;

    public function __construct(
        UserService $userService,
        EmailService $emailService,
        TokenManager $tokenManager,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->userService = $userService;
        $this->emailService = $emailService;
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
    }
    
    /**
     * Show login form
     */
    public function loginForm(Request $request, Response $response): Response {
        // Redirect if already logged in
        if ($this->isLoggedIn()) {
            return $response->redirect('/dashboard');
        }
        
        // Get redirect URL if any
        $redirect = $request->input('redirect_after_login', '/dashboard'); // Assuming redirect_after_login might be a query param too
        if (!$redirect) {
            $redirect = $request->session('redirect_after_login', '/dashboard');
        }
        
        // Show login form
        $data = [
            'title' => 'Login',
            'redirect' => $redirect
        ];
        
        return $response->render('auth/login', $data);
    }
    
    /**
     * Handle login form submission
     */
    public function login(Request $request, Response $response): Response {
        // Redirect if already logged in
        if ($this->isLoggedIn()) {
            return $response->redirect('/dashboard');
        }
        
        // Get form data
        $email = $request->input('email', '');
        $password = $request->input('password', '');
        $remember = $request->has('remember');
        $redirect = $request->input('redirect', '/dashboard');
        
        try {
            // Attempt login using UserService
            $result = $this->userService->login($email, $password, $remember);
            
            if ($result['success']) {
                // Set session data
                $user = $result['data']['user'];
                $request->setSession('user_id', $user->id);
                $request->setSession('user_email', $user->email);
                $request->setSession('user_name', $user->name);
                $request->regenerateSession();
                
                // Clear redirect URL
                $request->removeSession('redirect_after_login');
                
                return $response->redirect($redirect);
            } else {
                // Login failed
                $request->flash('error', $result['message']);
                $request->flash('old', ['email' => $email]);
                return $response->redirect('/login');
            }
            
        } catch (ValidationException $e) {
                $request->flash('error', $e->getMessage());
                $request->flash('old', ['email' => $email]);
                return $response->redirect('/login');
            } catch (Exception $e) {
                $this->logger->error('Login error: ' . $e->getMessage());
                $request->flash('error', 'An error occurred. Please try again.');
                return $response->redirect('/login');
        }
    }
    
    /**
     * Logout user
     */
    public function logout(Request $request, Response $response): Response {
        // Use UserService to handle logout
        $this->userService->logout();
        
        // Clear session data
        $request->clearSession();
        $request->regenerateSession();
        
        // Set success message
        $request->flash('success', 'You have been successfully logged out.');
        
        // Redirect to login page
        return $response->redirect('/login');
    }
    
    /**
     * Show registration form
     */
    public function registerForm(Request $request, Response $response): Response {
        // Redirect if already logged in
        if ($this->isLoggedIn()) {
            return $response->redirect('/dashboard');
        }
        
        $data = [
            'title' => 'Register'
        ];
        
        return $response->render('auth/register', $data);
    }
    
    /**
     * Handle registration form submission
     */
    public function processRegister(Request $request, Response $response): Response
    {
        // Redirect if already logged in
        if ($this->isLoggedIn()) {
            return $response->redirect('/dashboard');
        }

        // Get form data
        $name = $request->input('name', '');
        $email = $request->input('email', '');
        $password = $request->input('password', '');
        $confirm_password = $request->input('confirm_password', '');
        
        try {
            // Attempt registration using UserService
            $result = $this->userService->register([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'confirm_password' => $confirm_password
            ]);
            
            if ($result['success']) {
                // Registration successful
                $request->flash('success', $result['message']);
                return $response->redirect('/login');
            } else {
                // Registration failed
                $request->flash('error', $result['message']);
                $request->flash('old', [
                    'name' => $name,
                    'email' => $email
                ]);
                return $response->redirect('/register');
            }
            
        } catch (ValidationException $e) {
                $request->flash('error', $e->getMessage());
                $request->flash('old', [
                    'name' => $name,
                    'email' => $email
                ]);
                return $response->redirect('/register');
            } catch (Exception $e) {
                $this->logger->error('Registration error: ' . $e->getMessage());
                $request->flash('error', 'An error occurred during registration. Please try again.');
                $request->flash('old', [
                    'name' => $name,
                    'email' => $email
                ]);
                return $response->redirect('/register');
        }
    }

    /**
     * Show the form for requesting a password reset link.
     */
    public function showLinkRequestForm(Request $request, Response $response): Response {
        if ($this->isLoggedIn()) {
            return $response->redirect('/dashboard');
        }
        
        return $response->render('auth/password/email', ['title' => 'Reset Password']);
    }

    /**
     * Handle the request to send a password reset link.
     */
    public function sendResetLinkEmail(Request $request, Response $response): Response {
        if ($this->isLoggedIn()) {
            return $response->redirect('/dashboard');
        }

        $email = $request->input('email', '');

        try {
            $result = $this->userService->sendPasswordResetLink($email);
            
            if ($result['success']) {
                $request->flash('success', $result['message']);
            } else {
                $request->flash('error', $result['message']);
            }
        } catch (Exception $e) {
            $this->logger->error('Password reset error: ' . $e->getMessage());
            $request->flash('error', 'An error occurred. Please try again.');
        }
        
        return $response->redirect('/password/reset');
    }

    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request, Response $response, $token): Response {
        if ($this->isLoggedIn()) {
            return $this->redirect('/dashboard');
        }
        
        try {
            $tokenData = $this->tokenManager->validateToken($token, 'password_reset');
            
            if (!$tokenData) {
                $request->flash('error', 'Invalid or expired password reset token.');
                return $response->redirect('/password/reset');
            }
            
            return $response->render('auth/password/reset', [
                'title' => 'Reset Your Password',
                'token' => $token,
                'email' => $tokenData['email']
            ]);
        } catch (Exception $e) {
            $this->logger->error('Password reset form error: ' . $e->getMessage());
            $request->flash('error', 'An error occurred. Please try again.');
            return $response->redirect('/password/reset');
        }
    }

    /**
     * Handle the actual password reset.
     */
    public function resetPassword(Request $request, Response $response): Response {
        if ($this->isLoggedIn()) {
            return $this->redirect('/dashboard');
        }
        
        $token = $request->input('token', '');
        $email = $request->input('email', '');
        $password = $request->input('password', '');
        $confirmPassword = $request->input('confirm_password', '');
        
        try {
            $result = $this->userService->resetPassword($token, $email, $password, $confirmPassword);
            
            if ($result['success']) {
                $request->flash('success', $result['message']);
                return $response->redirect('/login');
            } else {
                $request->flash('error', $result['message']);
                return $response->redirect('/password/reset/' . urlencode($token) . '?email=' . urlencode($email));
            }
        } catch (Exception $e) {
            $this->logger->error('Password reset error: ' . $e->getMessage());
            $request->flash('error', 'An error occurred. Please try again.');
            return $response->redirect('/password/reset');
        }
    }
}
