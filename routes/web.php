<?php

/**
 * Web Routes
 * 
 * Here is where you can register web routes for your application.
 * These routes are loaded by the RouteServiceProvider within a group
 * which contains the "web" middleware group.
 */

// Home route
$router->get('/', function () {
    return view('welcome');
});

// About route
$router->get('/about', function () {
    return 'About CosmicHub Framework';
});

// Contact route
$router->get('/contact', 'ContactController@index');
$router->post('/contact', 'ContactController@store');

// User routes
$router->group(['prefix' => 'users'], function () use ($router) {
    $router->get('/', 'UserController@index');
    $router->get('/{id}', 'UserController@show');
    $router->post('/', 'UserController@store');
    $router->put('/{id}', 'UserController@update');
    $router->delete('/{id}', 'UserController@destroy');
});

// Admin routes with middleware
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () use ($router) {
    $router->get('/', 'AdminController@dashboard');
    $router->get('/users', 'AdminController@users');
    $router->get('/settings', 'AdminController@settings');
});