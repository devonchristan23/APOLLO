<?php
/**
 * APOLLO — Helper Request, Session User, & JSON Response
 * Memudahkan pengiriman output JSON berstandar CORS serta pembacaan body request.
 */

/**
 * Kirim response JSON dengan status HTTP dan header CORS lengkap, lalu hentikan eksekusi
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Tangani preflight request OPTIONS dari browser
 */
function apolloHandleCorsOptions() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        jsonResponse(['status' => 'ok']);
    }
}

/**
 * Baca data input gabungan dari $_POST dan JSON body (php://input)
 */
function apolloReadRequestData() {
    $inputData = [];
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $parsed = json_decode($rawInput, true);
        if (is_array($parsed)) {
            $inputData = $parsed;
        }
    }
    return array_merge($_POST, $inputData);
}

/**
 * Ambil data user yang sedang login dari PHP Session (null jika Guest)
 */
function apolloGetCurrentUser() {
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
        return [
            'id'       => (int)$_SESSION['user_id'],
            'username' => (string)$_SESSION['username'],
            'email'    => (string)($_SESSION['email'] ?? '')
        ];
    }
    return null;
}

/**
 * Lepas lock file PHP Session agar request paralel tidak saling menunggu
 */
function apolloCloseSessionWrite() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
