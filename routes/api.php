<?php

/**
 * API Routes
 * 
 * Here is where you can register API routes for your application.
 * These routes are loaded by the RouteServiceProvider within a group
 * which is assigned the "api" middleware group.
 */

// API version 1 routes
$router->group(['prefix' => 'api/v1', 'middleware' => ['api']], function () use ($router) {
    
    // Authentication routes
    $router->post('/auth/login', 'Api\AuthController@login');
    $router->post('/auth/register', 'Api\AuthController@register');
    $router->post('/auth/logout', 'Api\AuthController@logout')->middleware('auth');
    $router->get('/auth/me', 'Api\AuthController@me')->middleware('auth');
    
    // User API routes
    $router->group(['prefix' => 'users', 'middleware' => ['auth']], function () use ($router) {
        $router->get('/', 'Api\UserController@index');
        $router->get('/{id}', 'Api\UserController@show');
        $router->post('/', 'Api\UserController@store');
        $router->put('/{id}', 'Api\UserController@update');
        $router->delete('/{id}', 'Api\UserController@destroy');
    });
    
    // Posts API routes
    $router->group(['prefix' => 'posts'], function () use ($router) {
        $router->get('/', 'Api\PostController@index');
        $router->get('/{id}', 'Api\PostController@show');
        
        // Protected routes
        $router->group(['middleware' => ['auth']], function () use ($router) {
            $router->post('/', 'Api\PostController@store');
            $router->put('/{id}', 'Api\PostController@update');
            $router->delete('/{id}', 'Api\PostController@destroy');
        });
    });
    
    // Health check
    $router->get('/health', function () {
        return [
            'status' => 'ok',
            'timestamp' => time(),
            'version' => app()->version()
        ];
    });
});