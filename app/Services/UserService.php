<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\UserRepository;
use App\Models\User;

/**
 * User Service for handling user business logic
 */
class UserService extends Service
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
     * Create a new user
     * 
     * @param array $data User data
     * @return array
     */
    public function createUser(array $data)
    {
        try {
            // Validate input
            $validatedData = $this->validate($data, [
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'username' => 'required|min:3|max:50',
                'password' => 'required|min:6'
            ]);
            
            // Check if email already exists
            if ($this->userRepository->findByEmail($validatedData['email'])) {
                return $this->formatResponse(false, null, 'Email already exists', ['email' => ['Email is already taken']]);
            }
            
            // Check if username already exists
            if ($this->userRepository->findByUsername($validatedData['username'])) {
                return $this->formatResponse(false, null, 'Username already exists', ['username' => ['Username is already taken']]);
            }
            
            return $this->transaction(function() use ($validatedData) {
                // Hash password
                $validatedData['password'] = password_hash($validatedData['password'], PASSWORD_DEFAULT);
                $validatedData['status'] = 'active';
                $validatedData['created_at'] = date('Y-m-d H:i:s');
                $validatedData['updated_at'] = date('Y-m-d H:i:s');
                
                // Create user
                $userId = $this->userRepository->create($validatedData);
                $user = $this->userRepository->find($userId);
                
                $this->log('info', 'User created successfully', ['user_id' => $userId]);
                
                return $this->formatResponse(true, $user, 'User created successfully');
            });
            
        } catch (\InvalidArgumentException $e) {
            return $this->formatResponse(false, null, 'Validation failed', json_decode($e->getMessage(), true));
        } catch (\Exception $e) {
            $this->log('error', 'Failed to create user', ['error' => $e->getMessage()]);
            return $this->formatResponse(false, null, 'Failed to create user');
        }
    }
    
    /**
     * Update user information
     * 
     * @param int $userId User ID
     * @param array $data Update data
     * @return array
     */
    public function updateUser($userId, array $data)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->formatResponse(false, null, 'User not found');
            }
            
            // Validate input
            $rules = [];
            if (isset($data['name'])) $rules['name'] = 'min:2|max:100';
            if (isset($data['email'])) $rules['email'] = 'email';
            if (isset($data['username'])) $rules['username'] = 'min:3|max:50';
            if (isset($data['password'])) $rules['password'] = 'min:6';
            
            $validatedData = $this->validate($data, $rules);
            
            // Check email uniqueness if updating email
            if (isset($validatedData['email']) && $validatedData['email'] !== $user['email']) {
                if ($this->userRepository->findByEmail($validatedData['email'])) {
                    return $this->formatResponse(false, null, 'Email already exists', ['email' => ['Email is already taken']]);
                }
            }
            
            // Check username uniqueness if updating username
            if (isset($validatedData['username']) && $validatedData['username'] !== $user['username']) {
                if ($this->userRepository->findByUsername($validatedData['username'])) {
                    return $this->formatResponse(false, null, 'Username already exists', ['username' => ['Username is already taken']]);
                }
            }
            
            return $this->transaction(function() use ($userId, $validatedData) {
                // Hash password if provided
                if (isset($validatedData['password'])) {
                    $validatedData['password'] = password_hash($validatedData['password'], PASSWORD_DEFAULT);
                }
                
                $validatedData['updated_at'] = date('Y-m-d H:i:s');
                
                // Update user
                $this->userRepository->update($userId, $validatedData);
                $user = $this->userRepository->find($userId);
                
                $this->log('info', 'User updated successfully', ['user_id' => $userId]);
                
                return $this->formatResponse(true, $user, 'User updated successfully');
            });
            
        } catch (\InvalidArgumentException $e) {
            return $this->formatResponse(false, null, 'Validation failed', json_decode($e->getMessage(), true));
        } catch (\Exception $e) {
            $this->log('error', 'Failed to update user', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return $this->formatResponse(false, null, 'Failed to update user');
        }
    }
    
    /**
     * Authenticate user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array
     */
    public function authenticate($email, $password)
    {
        try {
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user) {
                return $this->formatResponse(false, null, 'Invalid credentials');
            }
            
            if (!password_verify($password, $user['password'])) {
                return $this->formatResponse(false, null, 'Invalid credentials');
            }
            
            if ($user['status'] !== 'active') {
                return $this->formatResponse(false, null, 'Account is not active');
            }
            
            // Update last login
            $this->userRepository->updateLastLogin($user['id']);
            $this->userRepository->incrementLoginCount($user['id']);
            
            // Remove password from response
            unset($user['password']);
            
            $this->log('info', 'User authenticated successfully', ['user_id' => $user['id']]);
            
            return $this->formatResponse(true, $user, 'Authentication successful');
            
        } catch (\Exception $e) {
            $this->log('error', 'Authentication failed', ['email' => $email, 'error' => $e->getMessage()]);
            return $this->formatResponse(false, null, 'Authentication failed');
        }
    }
    
    /**
     * Get user profile
     * 
     * @param int $userId User ID
     * @return array
     */
    public function getUserProfile($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            
            if (!$user) {
                return $this->formatResponse(false, null, 'User not found');
            }
            
            // Remove sensitive data
            unset($user['password']);
            
            return $this->formatResponse(true, $user);
            
        } catch (\Exception $e) {
            $this->log('error', 'Failed to get user profile', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return $this->formatResponse(false, null, 'Failed to get user profile');
        }
    }
    
    /**
     * Search users
     * 
     * @param string $search Search term
     * @param int $page Page number
     * @param int $limit Results per page
     * @return array
     */
    public function searchUsers($search, $page = 1, $limit = 20)
    {
        try {
            $users = $this->userRepository->search($search);
            
            // Remove sensitive data
            $users = array_map(function($user) {
                unset($user['password']);
                return $user;
            }, $users);
            
            // Simple pagination
            $offset = ($page - 1) * $limit;
            $paginatedUsers = array_slice($users, $offset, $limit);
            
            return $this->formatResponse(true, [
                'users' => $paginatedUsers,
                'total' => count($users),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(count($users) / $limit)
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Failed to search users', ['search' => $search, 'error' => $e->getMessage()]);
            return $this->formatResponse(false, null, 'Failed to search users');
        }
    }
    
    /**
     * Get user statistics
     * 
     * @return array
     */
    public function getUserStatistics()
    {
        try {
            $stats = $this->userRepository->getStatistics();
            return $this->formatResponse(true, $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Failed to get user statistics', ['error' => $e->getMessage()]);
            return $this->formatResponse(false, null, 'Failed to get user statistics');
        }
    }
    
    /**
     * Delete user
     * 
     * @param int $userId User ID
     * @return array
     */
    public function deleteUser($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            
            if (!$user) {
                return $this->formatResponse(false, null, 'User not found');
            }
            
            return $this->transaction(function() use ($userId) {
                $this->userRepository->delete($userId);
                
                $this->log('info', 'User deleted successfully', ['user_id' => $userId]);
                
                return $this->formatResponse(true, null, 'User deleted successfully');
            });
            
        } catch (\Exception $e) {
            $this->log('error', 'Failed to delete user', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return $this->formatResponse(false, null, 'Failed to delete user');
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null
     */
    public function getUserById($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            
            if (!$user) {
                return null;
            }
            
            // Remove sensitive data
            unset($user['password']);
            
            return $user;
            
        } catch (\Exception $e) {
            $this->log('error', 'Failed to get user by ID', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get all users
     * 
     * @return array
     */
    public function getAllUsers()
    {
        try {
            $users = $this->userRepository->findAll();
            
            // Remove sensitive data
            $users = array_map(function($user) {
                unset($user['password']);
                return $user;
            }, $users);
            
            return $users;
            
        } catch (\Exception $e) {
            $this->log('error', 'Failed to get all users', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Validate user data
     * 
     * @param array $data User data
     * @param int|null $userId User ID for updates (to exclude from uniqueness checks)
     * @return array Validation errors
     */
    public function validateUser(array $data, $userId = null)
    {
        $errors = [];
        
        try {
            // Basic validation rules
            $rules = [];
            if (isset($data['name'])) $rules['name'] = 'required|min:2|max:100';
            if (isset($data['email'])) $rules['email'] = 'required|email';
            if (isset($data['username'])) $rules['username'] = 'required|min:3|max:50';
            if (isset($data['password']) && !empty($data['password'])) $rules['password'] = 'min:6';
            
            // Validate basic rules
            $this->validate($data, $rules);
            
            // Check email uniqueness
            if (isset($data['email'])) {
                $existingUser = $this->userRepository->findByEmail($data['email']);
                if ($existingUser && (!$userId || $existingUser['id'] != $userId)) {
                    $errors['email'] = ['Email is already taken'];
                }
            }
            
            // Check username uniqueness
            if (isset($data['username'])) {
                $existingUser = $this->userRepository->findByUsername($data['username']);
                if ($existingUser && (!$userId || $existingUser['id'] != $userId)) {
                    $errors['username'] = ['Username is already taken'];
                }
            }
            
        } catch (\InvalidArgumentException $e) {
            $validationErrors = json_decode($e->getMessage(), true);
            if (is_array($validationErrors)) {
                $errors = array_merge($errors, $validationErrors);
            }
        } catch (\Exception $e) {
            $this->log('error', 'Validation error', ['error' => $e->getMessage()]);
            $errors['general'] = ['Validation failed'];
        }
        
        return $errors;
    }
}