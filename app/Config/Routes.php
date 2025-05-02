<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/login', 'Home::login');
$routes->get('/register', 'Home::register');

// Public Auth Routes
$routes->group('auth', function($routes) {
    $routes->post('register', 'AuthController::register');
    $routes->post('login', 'AuthController::login');
    $routes->get('/dashboard', 'Home::wallet');
});

$routes->get('/', 'Home::wallet');
// JWT-Protected Routes
$routes->group('/', ['filter' => 'jwt'], function($routes) {
    $routes->get('/dashboard', 'Home::wallet');
    $routes->get('/loaduserdata', 'WalletController::dataUserDashboard');
    $routes->post('/wallet/deposit', 'WalletController::deposit'); // ✅ Now works!
    $routes->post('/wallet/transfer', 'WalletController::transfer'); 
    $routes->post('/wallet/reverse/(:num)', 'WalletController::reverse/$1');
    $routes->post('/auth/logout', 'AuthController::logout');
});