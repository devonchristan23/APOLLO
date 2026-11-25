<?php
/**
 * APOLLO — Endpoint API Autentikasi User (Status, Register, Login, Logout)
 * Terhubung ke tabel users MySQL (XAMPP) dengan fallback otomatis ke localStorage di Vercel.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/auth_service.php';

apolloHandleCorsOptions();

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';
$data   = apolloReadRequestData();

if ($action === 'status') {
    $user = apolloGetCurrentUser();
    jsonResponse([
        'status'       => 'ok',
        'db_connected' => isDbConnected(),
        'logged_in'    => ($user !== null),
        'user'         => $user
    ]);
}

if ($action === 'logout') {
    apolloLogoutSession();
    jsonResponse([
        'status'    => 'ok',
        'logged_in' => false,
        'message'   => 'Logged out successfully'
    ]);
}

if (!isDbConnected()) {
    jsonResponse([
        'status'       => 'fallback',
        'db_connected' => false,
        'message'      => 'Using browser storage fallback for user accounts.'
    ]);
}

try {
    switch ($action) {
        case 'register':
            jsonResponse([
                'status'    => 'ok',
                'logged_in' => true,
                'message'   => 'Registrasi berhasil!',
                'user'      => apolloRegisterAccount($pdo, $data)
            ]);
            break;

        case 'login':
            jsonResponse([
                'status'    => 'ok',
                'logged_in' => true,
                'message'   => 'Login berhasil!',
                'user'      => apolloAuthenticateAccount($pdo, $data)
            ]);
            break;

        default:
            jsonResponse(['status' => 'error', 'message' => 'Unknown auth action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['status' => 'error', 'message' => 'Auth error: ' . $e->getMessage()], 500);
}
