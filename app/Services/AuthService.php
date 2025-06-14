<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\UserRepository;
use App\Models\User;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Authentication Service for handling user authentication
 */
class AuthService extends Service
{
    /**
     * @var UserRepository
     */
    protected $userRepository;
    
    /**
     * Initialize repositories
     * 
     * @return void
     */
    protected function initializeRepositories()
    {
        $this->userRepository = $this->getRepository('UserRepository');
    }
    
    /**
     * Check if user is logged in
     * 
     * @param Request $request
     * @return bool
     */
    public function isLoggedIn(Request $request)
    {
        return $request->getSession('user_id') !== null;
    }
    
    /**
     * Get the current authenticated user
     * 
     * @param Request $request
     * @return User|null
     */
    public function getCurrentUser(Request $request)
    {
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return null;
        }
        
        return $this->userRepository->find($userId);
    }
    
    /**
     * Login a user
     * 
     * @param array $credentials
     * @param Request $request
     * @return array
     */
    public function login(array $credentials, Request $request)
    {
        try {
            // Validate credentials
            $validatedData = $this->validate($credentials, [
                'email' => 'required|email',
                'password' => 'required'
            ]);
            
            // Find user by email
            $user = $this->userRepository->findByEmail($validatedData['email']);
            if (!$user) {
                return $this->error('Invalid credentials');
            }
            
            // Verify password
            if (!password_verify($validatedData['password'], $user['password'])) {
                return $this->error('Invalid credentials');
            }
            
            // Set session data
            $request->setSession('user_id', $user['id']);
            $request->setSession('user_name', $user['name']);
            $request->setSession('user_email', $user['email']);
            $request->setSession('user_role', $user['role'] ?? 'user');
            $request->setSession('last_activity', time());
            
            $this->log('info', 'User logged in successfully', ['user_id' => $user['id']]);
            
            return $this->success('Login successful', $user);
            
        } catch (\Exception $e) {
            $this->log('error', 'Login error: ' . $e->getMessage(), $credentials);
            return $this->error('An error occurred during login');
        }
    }
    
    /**
     * Logout a user
     * 
     * @param Request $request
     * @return array
     */
    public function logout(Request $request)
    {
        try {
            $userId = $request->getSession('user_id');
            
            // Clear session data
            $request->destroySession();
            
            $this->log('info', 'User logged out successfully', ['user_id' => $userId]);
            
            return $this->success('Logout successful');
            
        } catch (\Exception $e) {
            $this->log('error', 'Logout error: ' . $e->getMessage());
            return $this->error('An error occurred during logout');
        }
    }
    
    /**
     * Check if user has required role
     * 
     * @param Request $request
     * @param string|array $roles
     * @return bool
     */
    public function hasRole(Request $request, $roles)
    {
        $userRole = $request->getSession('user_role', 'user');
        
        if (is_string($roles)) {
            return $userRole === $roles;
        }
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return false;
    }
    
    /**
     * Require user to be logged in
     * 
     * @param Request $request
     * @param string $redirectUrl
     * @return Response|null
     */
    public function requireLogin(Request $request, $redirectUrl = '/login')
    {
        if (!$this->isLoggedIn($request)) {
            $request->setSession('redirect_after_login', $request->getUri());
            return new Response('', 302, ['Location' => $redirectUrl]);
        }
        
        return null;
    }
    
    /**
     * Require user to be guest (not logged in)
     * 
     * @param Request $request
     * @param string $redirectUrl
     * @return Response|null
     */
    public function requireGuest(Request $request, $redirectUrl = '/dashboard')
    {
        if ($this->isLoggedIn($request)) {
            return new Response('', 302, ['Location' => $redirectUrl]);
        }
        
        return null;
    }
    
    /**
     * Require user to have specific role
     * 
     * @param Request $request
     * @param string|array $roles
     * @param string $redirectUrl
     * @return Response|null
     */
    public function requireRole(Request $request, $roles, $redirectUrl = '/')
    {
        if (!$this->isLoggedIn($request)) {
            return new Response('', 302, ['Location' => '/login']);
        }
        
        if (!$this->hasRole($request, $roles)) {
            $request->setSession('error', 'You do not have permission to access this page');
            return new Response('', 302, ['Location' => $redirectUrl]);
        }
        
        return null;
    }
    
    /**
     * Get user data for session
     * 
     * @param Request $request
     * @return array
     */
    public function getUserData(Request $request)
    {
        return [
            'id' => $request->getSession('user_id'),
            'name' => $request->getSession('user_name'),
            'email' => $request->getSession('user_email'),
            'role' => $request->getSession('user_role', 'user')
        ];
    }
    
    /**
     * Register a new user
     * 
     * @param array $userData
     * @param Request $request
     * @return array
     */
    public function register(array $userData, Request $request)
    {
        try {
            // Validate input
            $validatedData = $this->validate($userData, [
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'username' => 'required|min:3|max:50',
                'password' => 'required|min:6'
            ]);
            
            // Check if email already exists
            if ($this->userRepository->findByEmail($validatedData['email'])) {
                return $this->error('Email already exists');
            }
            
            // Hash password
            $validatedData['password'] = password_hash($validatedData['password'], PASSWORD_DEFAULT);
            
            // Create user
            $userId = $this->userRepository->create($validatedData);
            if (!$userId) {
                return $this->error('Failed to create user');
            }
            
            // Get created user
            $user = $this->userRepository->find($userId);
            
            // Auto-login the user
            $request->setSession('user_id', $user['id']);
            $request->setSession('user_name', $user['name']);
            $request->setSession('user_email', $user['email']);
            $request->setSession('user_role', $user['role'] ?? 'user');
            $request->setSession('last_activity', time());
            
            $this->log('info', 'User registered and logged in successfully', ['user_id' => $user['id']]);
            
            return $this->success('Registration successful', $user);
            
        } catch (\Exception $e) {
            $this->log('error', 'Registration error: ' . $e->getMessage(), $userData);
            return $this->error('An error occurred during registration');
        }
    }
}