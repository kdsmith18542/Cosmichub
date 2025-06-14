<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\UserService;
use App\Services\AuthService;
use App\Exceptions\ValidationException;
use App\Core\View\View;
use Exception;

/**
 * User Controller
 * 
 * Handles user-related operations and CRUD functionality.
 */
class UserController extends Controller
{
    /** @var UserService */
    private UserService $userService;
    
    /** @var AuthService */
    private AuthService $authService;
    
    public function __construct(
        UserService $userService,
        AuthService $authService
    ) {
        parent::__construct();
        $this->userService = $userService;
        $this->authService = $authService;
    }
    public function index(Request $request, Response $response): Response
    {
        try {
            $users = $this->userService->getAllUsers();
            
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => true,
                    'data' => $users
                ]);
            }
            
            return $response->render('users.index', [
                'title' => 'Users',
                'users' => $users
            ]);
            
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => false,
                    'message' => 'Failed to load users'
                ], 500);
            }
            
            $request->flash('error', 'Failed to load users');
            return $response->redirect('/dashboard');
        }
    }
    
    /**
     * Show the form for creating a new user.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        return $response->render('users.create', [
            'title' => 'Create User'
        ]);
    }
    
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->all();
            
            // Validate input using service
            $errors = $this->userService->validateUser($data);
            if (!empty($errors)) {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => false,
                        'errors' => $errors
                    ], 422);
                }
                
                $request->flash('errors', $errors);
                return $response->redirect('/users/create');
            }
            
            $result = $this->userService->createUser($data);
            
            if ($result['success']) {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => true,
                        'data' => $result['data'],
                        'message' => 'User created successfully'
                    ], 201);
                }
                
                $request->flash('success', 'User created successfully');
                return $response->redirect('/users');
            } else {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => false,
                        'message' => $result['message']
                    ], 400);
                }
                
                $request->flash('error', $result['message']);
                return $response->redirect('/users/create');
            }
            
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => false,
                    'message' => 'Failed to create user'
                ], 500);
            }
            
            $request->flash('error', 'Failed to create user');
            return $response->redirect('/users/create');
        }
    }
    
    /**
     * Display the specified user.
     *
     * @param Request $request
     * @param Response $response
     * @param int $id
     * @return Response
     */
    public function show(Request $request, Response $response, $id): Response
    {
        try {
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => false,
                        'message' => 'User not found'
                    ], 404);
                }
                
                return $response->render('error', [
                    'title' => 'User Not Found',
                    'message' => 'The requested user could not be found'
                ]);
            }
            
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => true,
                    'data' => $user
                ]);
            }
            
            return $response->render('users.show', [
                'title' => 'User Details',
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => false,
                    'message' => 'Failed to retrieve user',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return $response->render('error', [
                'title' => 'Error',
                'message' => 'Failed to retrieve user'
            ]);
        }
    }
    
    /**
     * Show the form for editing the specified user.
     *
     * @param Request $request
     * @param Response $response
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, Response $response, $id): Response
    {
        try {
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                return $response->render('error', [
                    'title' => 'User Not Found',
                    'message' => 'The requested user could not be found'
                ]);
            }
            
            return $response->render('users.edit', [
                'title' => 'Edit User',
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            return $response->render('error', [
                'title' => 'Error',
                'message' => 'Failed to retrieve user for editing'
            ]);
        }
    }
    
    /**
     * Update the specified user.
     *
     * @param Request $request
     * @param Response $response
     * @param int $id
     * @return Response
     */
    public function update(Request $request, Response $response, $id): Response
    {
        try {
            $data = $request->all();
            
            // Validate input using service
            $errors = $this->userService->validateUser($data, $id);
            if (!empty($errors)) {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => false,
                        'errors' => $errors
                    ], 422);
                }
                
                $request->flash('errors', $errors);
                return $response->redirect('/users/' . $id . '/edit');
            }
            
            // Check if user exists
            $user = $this->userService->getUserById($id);
            if (!$user) {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => false,
                        'message' => 'User not found'
                    ], 404);
                }
                
                $request->flash('error', 'User not found');
                return $response->redirect('/users');
            }
            
            $result = $this->userService->updateUser($id, $data);
            
            if ($result['success']) {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => true,
                        'data' => $result['data'],
                        'message' => 'User updated successfully'
                    ]);
                }
                
                $request->flash('success', 'User updated successfully');
                return $response->redirect('/users');
            } else {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => false,
                        'message' => $result['message']
                    ], 400);
                }
                
                $request->flash('error', $result['message']);
                return $response->redirect('/users/' . $id . '/edit');
            }
            
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => false,
                    'message' => 'Failed to update user'
                ], 500);
            }
            
            $request->flash('error', 'Failed to update user');
            return $response->redirect('/users/' . $id . '/edit');
        }
    }
    
    /**
     * Remove the specified user.
     *
     * @param Request $request
     * @param Response $response
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request, Response $response, $id): Response
    {
        try {
            $result = $this->userService->deleteUser($id);
            
            if (!$result['success']) {
                if ($request->wantsJson()) {
                    return $response->json([
                        'success' => false,
                        'message' => $result['message']
                    ], 404);
                }
                
                return $response->render('error', [
                    'title' => 'Error',
                    'message' => $result['message']
                ]);
            }
            
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }
            
            $request->flash('success', $result['message']);
            return $response->redirect('/users');
            
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return $response->json([
                    'success' => false,
                    'message' => 'Failed to delete user',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            $request->flash('error', 'Failed to delete user');
            return $response->redirect('/users');
        }
    }
    
}