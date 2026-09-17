<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Auth\LoginController;
use App\Controllers\Counter\CounterController;
use App\Controllers\Counter\SlipController;
use App\Controllers\Counter\VerifyController;
use App\Controllers\Counter\PhotoController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\MasterDataController;
use App\Controllers\Admin\VoterLogController;

/** @var RouteCollection $routes */

$routes->get('/', static function () {
    return redirect()->to(session()->get('user_id') ? (session()->get('role') === 'admin' ? '/admin' : '/counter') : '/login');
});

// ---------- Auth (public) ----------
$routes->get('login', [LoginController::class, 'index']);
$routes->post('login', [LoginController::class, 'attempt']);
$routes->get('logout', [LoginController::class, 'logout']);

// ---------- Public QR verification + photo serving (no auth) ----------
$routes->get('verify/(:segment)', [VerifyController::class, 'show']);
$routes->get('photos/(:segment)', [PhotoController::class, 'show']);

// ---------- Counter operator ----------
$routes->group('counter', ['filter' => ['auth', 'role:operator,admin']], static function ($routes) {
    $routes->get('/', [CounterController::class, 'index']);
    $routes->post('lookup', [CounterController::class, 'lookup']);
    $routes->post('issue', [CounterController::class, 'issue']);
    $routes->get('stats', [CounterController::class, 'stats']);
});

// Slip view/reprint — usable by whoever issued it (operator) or admin.
$routes->group('slip', ['filter' => ['auth', 'role:operator,admin']], static function ($routes) {
    $routes->get('(:segment)', [SlipController::class, 'show']);
    $routes->get('(:segment)/qr', [SlipController::class, 'qr']);
});

// ---------- Admin ----------
$routes->group('admin', ['filter' => ['auth', 'role:admin']], static function ($routes) {
    $routes->get('/', [DashboardController::class, 'index']);

    $routes->get('master-data', [MasterDataController::class, 'index']);
    $routes->post('master-data/preview-excel', [MasterDataController::class, 'previewExcel']);
    $routes->post('master-data/commit-excel', [MasterDataController::class, 'commitExcel']);
    $routes->post('master-data/preview-photos', [MasterDataController::class, 'previewPhotos']);
    $routes->post('master-data/commit-photos', [MasterDataController::class, 'commitPhotos']);

    $routes->get('voter-log', [VoterLogController::class, 'index']);
    $routes->post('voter-log/void/(:segment)', [VoterLogController::class, 'void']);
    $routes->get('voter-log/export', [VoterLogController::class, 'export']);
});
