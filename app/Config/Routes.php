<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API ROUTES CORE SYSTEM

$routes->get('setup/init', 'Setup::init');

$routes->group('api', function($routes) {
    // --- Authentication ---
    $routes->post('login', 'ApiController::login');
    
    // --- Transaksi Membership ---
    $routes->post('partner/register', 'ApiController::registerPartner');
    $routes->post('member/create', 'ApiController::createMember');
    $routes->post('point/add', 'ApiController::addPoints');
    $routes->post('member/balance', 'ApiController::getBalance');
    $routes->post('point/redeem', 'ApiController::redeemPoints');

    // --- Data Viewing (GET) ---
    $routes->get('member/list', 'ApiController::listMembers');
    $routes->get('partner/list', 'ApiController::listPartners');
    $routes->get('point/history/(:any)', 'ApiController::getHistory/$1');

    // --- Integrasi ---
    $routes->post('integration/pay-parking', 'IntegrationController::payParking');
});


$routes->get('bikin-tabel', function() {
    $migrate = \Config\Services::migrations();
    try {
        $migrate->latest();
        return '✅ BERHASIL! Tabel Database sudah dibuat. Silakan tes di Postman.';
    } catch (\Throwable $e) {
        return "❌ GAGAL: " . $e->getMessage();
    }
});