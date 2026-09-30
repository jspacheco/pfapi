<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('v1', function($routes) {
    $routes->get('/', 'Home::index');

    $routes->post('auth_login', 'AuthController::auth_login');

    $routes->group('', ['filter' => 'jwt'], function($routes) {
        $routes->post('search', 'SearchController::search');
        $routes->post('set_claim_activity', 'ClaimController::set_claim_activity');
        $routes->post('check_eligibility', 'ClaimController::check_eligibility');
        
    });
});