<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Auth\LoginController;
use App\Controllers\Counter\CounterController;
use App\Controllers\Counter\SlipController;
use App\Controllers\Counter\VerifyController;
use App\Controllers\Counter\PhotoController;
use App\Controllers\Exit\ExitScanController;
use App\Controllers\Membership\MembershipController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\MasterDataController;
use App\Controllers\Admin\VoterLogController;
use App\Controllers\Admin\MembershipAdminController;
use App\Controllers\Admin\MembershipCardController;

/** @var RouteCollection $routes */

$routes->get('/', static function () {
    if (! session()->get('user_id')) {
        return redirect()->to('/login');
    }

    return redirect()->to(match (session()->get('role')) {
        'admin' => '/admin',
        'exit_operator' => '/exit',
        default => '/counter',
    });
});

// ---------- Auth (public) ----------
$routes->get('login', [LoginController::class, 'index']);
$routes->post('login', [LoginController::class, 'attempt']);
$routes->get('logout', [LoginController::class, 'logout']);

// ---------- Public QR verification + photo serving (no auth) ----------
$routes->get('verify/(:segment)', [VerifyController::class, 'show']);
$routes->get('photos/(:segment)', [PhotoController::class, 'show']);

// ---------- Public membership self-registration (no auth) ----------
$routes->group('membership', static function ($routes) {
    $routes->get('apply', [MembershipController::class, 'apply']);
    $routes->post('apply', [MembershipController::class, 'submit']);
    $routes->get('pay/(:segment)', [MembershipController::class, 'pay']);
    $routes->post('pay/(:segment)/confirm', [MembershipController::class, 'confirmPayment']);
    $routes->get('status', [MembershipController::class, 'statusLookup']);
    $routes->get('status/(:segment)', [MembershipController::class, 'status']);
});

// ---------- Counter operator ----------
$routes->group('counter', ['filter' => ['auth', 'role:operator,admin']], static function ($routes) {
    $routes->get('/', [CounterController::class, 'index']);
    $routes->post('lookup', [CounterController::class, 'lookup']);
    $routes->post('issue', [CounterController::class, 'issue']);
    $routes->get('stats', [CounterController::class, 'stats']);
});

// Slip view/reprint — usable by whoever issued it (operator) or admin.
$routes->group('slip', ['filter' => ['auth', 'role:operator,admin,exit_operator']], static function ($routes) {
    $routes->get('(:segment)', [SlipController::class, 'show']);
    $routes->get('(:segment)/qr', [SlipController::class, 'qr']);
});

// ---------- Exit desk (EVM vote confirmation) ----------
// Members surrender their slip after voting at the EVM; an exit-desk
// operator (or admin, as a backup station) scans its QR here to confirm
// the slip turned into an actual cast ballot, not just an issued one.
$routes->group('exit', ['filter' => ['auth', 'role:exit_operator,admin']], static function ($routes) {
    $routes->get('/', [ExitScanController::class, 'index']);
    $routes->post('scan', [ExitScanController::class, 'scan']);
    $routes->get('stats', [ExitScanController::class, 'stats']);
});

// ---------- Admin ----------
$routes->group('admin', ['filter' => ['auth', 'role:admin']], static function ($routes) {
    $routes->get('/', [DashboardController::class, 'index']);

    $routes->get('master-data', [MasterDataController::class, 'index']);
    $routes->post('master-data/preview-excel', [MasterDataController::class, 'previewExcel']);
    $routes->post('master-data/commit-excel', [MasterDataController::class, 'commitExcel']);
    $routes->post('master-data/preview-photos', [MasterDataController::class, 'previewPhotos']);
    $routes->post('master-data/commit-photos', [MasterDataController::class, 'commitPhotos']);
    $routes->post('master-data/sync-zoho', [MasterDataController::class, 'syncZoho']);
    $routes->post('master-data/commit-zoho', [MasterDataController::class, 'commitZoho']);

    $routes->get('voter-log', [VoterLogController::class, 'index']);
    $routes->post('voter-log/void/(:segment)', [VoterLogController::class, 'void']);
    $routes->get('voter-log/export', [VoterLogController::class, 'export']);

    // ---- Membership management ----
    $routes->get('membership', [MembershipAdminController::class, 'index']);
    $routes->get('membership/document/(:num)', [MembershipAdminController::class, 'downloadDocument']);
    $routes->get('membership/card/(:segment)', [MembershipCardController::class, 'show']);
    $routes->get('membership/(:num)', [MembershipAdminController::class, 'show']);
    $routes->post('membership/(:num)/committee/present', [MembershipAdminController::class, 'presentToCommittee']);
    $routes->post('membership/(:num)/committee/recommend', [MembershipAdminController::class, 'recommendByCommittee']);
    $routes->post('membership/(:num)/committee/reject', [MembershipAdminController::class, 'rejectByCommittee']);
    $routes->post('membership/(:num)/managing/present', [MembershipAdminController::class, 'presentToManagingCommittee']);
    $routes->post('membership/(:num)/managing/approve', [MembershipAdminController::class, 'approveByManagingCommittee']);
    $routes->post('membership/(:num)/managing/reject', [MembershipAdminController::class, 'rejectByManagingCommittee']);
    $routes->post('membership/(:num)/register', [MembershipAdminController::class, 'register']);
    $routes->post('membership/(:num)/rep/(:num)/rfid', [MembershipAdminController::class, 'assignRfid']);
});
