<?php
/**
 * APOLLO — Bootstrap Utama & Koneksi Database PDO MySQL (100% PHP Native)
 * Mengatur session, koneksi MySQL (XAMPP / Cloud), serta memuat modul helper inti.
 */

// Matikan notice/warning agar output JSON API selalu bersih
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Mulai PHP Session untuk akun user & pelacakan sesi pengunjung
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

// 1. Muat konstanta aplikasi
require_once __DIR__ . '/constants.php';

// 2. Konfigurasi koneksi MySQL (default XAMPP lokal, mendukung environment variables)
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'db_apollo';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_port = getenv('DB_PORT') ?: '3306';

$pdo = null;
$db_connected = false;

// Lewati percobaan koneksi ke localhost jika berjalan di Vercel tanpa database cloud eksternal
$hasExplicitRemoteDb = (
    getenv('DB_HOST') !== false &&
    getenv('DB_HOST') !== '' &&
    getenv('DB_HOST') !== '127.0.0.1' &&
    getenv('DB_HOST') !== 'localhost'
);

if (!apolloIsVercelRuntime() || $hasExplicitRemoteDb) {
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => $hasExplicitRemoteDb ? 3 : 1,
        ];

        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        $db_connected = true;
    } catch (Throwable $e) {
        $pdo = null;
        $db_connected = false;
    }
}

/**
 * Cek apakah koneksi MySQL sedang aktif
 */
function isDbConnected() {
    global $db_connected, $pdo;
    return $db_connected && $pdo !== null;
}

// 3. Muat seluruh helper modular agar langsung siap dipakai oleh api/ dan services/
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/http_client.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/ytdlp_helper.php';
require_once __DIR__ . '/music_filter.php';
require_once __DIR__ . '/../includes/ytm_parser.php';
