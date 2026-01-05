<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API ROUTES CORE SYSTEM
$routes->group('api', function($routes) {
    // 1. Daftar Mitra Baru
    $routes->post('partner/register', 'ApiController::registerPartner');
    
    // 2. Kelola Member
    $routes->post('member/create', 'ApiController::createMember');
    
    // 3. Transaksi Poin
    $routes->post('point/add', 'ApiController::addPoints');

    // 4. Cek Saldo (BARU)
    $routes->post('member/balance', 'ApiController::getBalance');

    $routes->post('point/redeem', 'ApiController::redeemPoints');
}); 


// --- JALUR KHUSUS SETUP DATABASE (Di luar grup API) ---
$routes->get('bikin-tabel', function() {
    $migrate = \Config\Services::migrations();
    try {
        $migrate->latest();
        return '✅ BERHASIL! Tabel Database sudah dibuat. Silakan tes di Postman.';
    } catch (\Throwable $e) {
        return "❌ GAGAL: " . $e->getMessage();
    }
});