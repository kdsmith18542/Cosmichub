<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication (login, registration, logout, etc.)
 */

namespace App\Controllers;

use App\Libraries\Controller;
use App\Models\User;
use Exception;
use PDOException;

/**
 * @property \App\Models\User $userModel
 */

class AuthController extends Controller {
    /** @var User */
    protected $userModel;
    
    public function __construct() {
        // Initialize the User model
        $this->userModel = new User();
    }
    
    /**
     * Show login form
     */
    public function loginForm() {
        // Redirect if already logged in
        $this->requireGuest('/dashboard');
        
        // Get redirect URL if any
        $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
        
        // Get flash messages
        $error = $this->getFlash('error');
        $old = $this->getFlash('old', []);
        
        // Show login form
        $data = [
            'title' => 'Login',
            'error' => $error,
            'email' => $old['email'] ?? '',
            'redirect' => $redirect
        ];
        
        // Load the login view
        $this->view('auth/login', $data);
    }
    
    /**
     * Handle login form submission
     */
    public function login() {
        // Redirect if already logged in
        $this->requireGuest('/dashboard');
        
        // Verify CSRF token
        if (!csrf_verify('login_form', false)) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/login');
            return;
        }
        
        // Validate input
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        $redirect = $_POST['redirect'] ?? '/dashboard';
        
        // Store old input for repopulating form
        $this->setFlash('old', [
            'email' => $email
        ]);
        
        // Basic validation
        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Both email and password are required');
            $this->redirect('/login');
            return;
        }
        
        try {
            // Find user by email
            $user = $this->userModel->findByEmail($email);
            
            // Verify password
            if ($user && $user->verifyPassword($password)) {
                // Check if email is verified
                if (!$user->hasVerifiedEmail()) {
                    // Resend verification email automatically or guide user
                    $emailService = new \App\Services\EmailService();
                    $emailService->sendVerificationEmail($user); // Attempt to resend
                    
                    Session::setFlash('warning', 'Your email address is not verified. A new verification link has been sent to your email. Please check your inbox (and spam folder).');
                    // Store intended URL if any, so user can be redirected after verification
                    if (Session::has('redirect_url')) {
                        // Keep it for after verification
                    } else {
                        // Potentially set a default redirect after verification, like dashboard
                        // Session::set('redirect_after_verification', '/dashboard');
                    }
                    return redirect('/email/verify/notice');
                }
                
                // Set auth session
                $this->setAuthSession([
                    'id' => $user->getAttribute('id'),
                    'name' => $user->getAttribute('name'),
                    'email' => $user->getAttribute('email'),
                    'role' => $user->getAttribute('role') ?? 'user'
                ]);
                
                // Clear any redirect URL
                unset($_SESSION['redirect_after_login']);
                
                // Update last login
                $user->setAttribute('last_login_at', date('Y-m-d H:i:s'));
                $user->save();
                
                $this->redirect($redirect);
                
                // Redirect to dashboard
                header('Location: /dashboard');
                exit;
            } else {
                $this->setFlash('error', 'Invalid email or password');
                $this->redirect('/login');
            }
            
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred. Please try again.');
            $this->redirect('/login');
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear auth session
        $this->clearAuthSession();
        
        // Set success message
        $this->setFlash('success', 'You have been successfully logged out.');
        
        // Redirect to login page
        $this->redirect('/login');
        
        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }
    

    
    /**
     * Handle registration form submission
     */
    public function processRegister(Request $request)
    {
        // Guest only
        if (Auth::is_logged_in()) {
            return redirect('/dashboard');
        }

        // CSRF Check
        if (!csrf_verify($request)) { // Using the helper function
            Session::setFlash('error', 'Invalid request. Please try again.');
            // Store old input to repopulate form
            Session::setFlash('old_input', $request->all());
            return redirect('/register');
        }
        
        // Validate input
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Store old input for repopulating form
        $this->setFlash('old', [
            'name' => $name,
            'email' => $email
        ]);
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Name is required';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters long';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Name cannot be longer than 100 characters';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } elseif (strlen($email) > 255) {
            $errors[] = 'Email cannot be longer than 255 characters';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        } elseif (strlen($password) > 255) {
            $errors[] = 'Password cannot be longer than 255 characters';
        } elseif (!preg_match('/[A-Z]/', $password) || 
                 !preg_match('/[a-z]/', $password) || 
                 !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        // If there are validation errors, redirect back with errors
        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            $this->redirect('/register');
            return;
        }
        
        try {
            // Begin transaction if supported
            if (method_exists($this, 'beginTransaction')) {
                $this->beginTransaction();
            }
            
            // Check if email already exists
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                throw new Exception('This email is already registered');
            }
            
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Prepare user data
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => 'user', // Default role
                'status' => 'pending', // Will be set to active after email verification
                'credits' => 0, // New users start with 0 credits
                'subscription_status' => 'inactive',
                'email_verified_at' => null,
                'email_verification_token' => null,
                'email_verification_sent_at' => null,
                'remember_token' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Create the user
            $user = new User();
            $user->fill($userData);
            
            if (!$user->save()) {
                throw new Exception('Failed to create user account');
            }
            
            // Commit transaction if it was started
            if (method_exists($this, 'commitTransaction')) {
                $this->commitTransaction();
            }
            
            // Generate email verification token
            $verificationToken = $user->createEmailVerificationToken();
            
            // Build verification URL
            $verificationUrl = url("/email/verify/{$user->id}/{$verificationToken}");
            
            // Send verification email
            $emailService = new \App\Services\EmailService();
            $emailResult = $emailService->sendVerificationEmail($user);

            if ($emailResult['success']) {
                Session::setFlash('success', 'Registration successful! Please check your email to verify your account. ' . ($emailResult['message'] ?? ''));
                return redirect('/email/verify/notice');
            } else {
                Logger::error('Failed to send verification email for user ID: ' . $user->id . '. Reason: ' . ($emailResult['message'] ?? 'Unknown error'));
                // Even if email fails, user is created. They can resend from notice page or login attempt.
                Session::setFlash('warning', 'Registration successful, but we encountered an issue sending your verification email. Please try logging in to resend the verification link or visit the email verification page.');
                return redirect('/email/verify/notice');
            }
            
        } catch (Exception $e) {
            // Rollback transaction if it was started
            if (method_exists($this, 'rollbackTransaction')) {
                $this->rollbackTransaction();
            }
            
            // Log the error
            error_log('Registration error: ' . $e->getMessage());
            
            // Set error message
            $this->setFlash('error', $e->getMessage());
            
            // Redirect back to registration form
            $this->redirect('/register');
        }
    }
}
