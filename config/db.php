<?php
/**
 * Apollo Minimalist Music Player - Native PHP Database & Session Configuration
 * Pure PHP Native (PDO MySQL) for XAMPP / Apache / PHP Server
 */

// Report errors cleanly in JSON APIs
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Initialize native PHP session for User Accounts & Visitor Session Tracking
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

// Optional YouTube Data API v3 Key (if provided by user)
define('YOUTUBE_API_KEY', getenv('YOUTUBE_API_KEY') ?: '');

// Curated Piped & Invidious Public Instances for Audio & Search fallback
define('PIPED_INSTANCES', [
    'https://api.piped.private.coffee',
    'https://pipedapi.leptons.xyz',
    'https://pipedapi.tokhmi.xyz',
    'https://piped.video/api/v1'
]);

define('INVIDIOUS_INSTANCES', [
    'https://yewtu.be',
    'https://inv.nadeko.net',
    'https://invidious.nerdvpn.de',
    'https://invidious.projectsegfau.lt'
]);

// Database Connection Settings for XAMPP
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'db_apollo';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_port = getenv('DB_PORT') ?: '3306';

$pdo = null;
$db_connected = false;

try {
    // 1-second timeout to avoid hanging if MySQL is not running (e.g. in Vercel)
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 1,
    ];
    
    // Test if MySQL is reachable
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $db_connected = true;
} catch (Throwable $e) {
    // Graceful fallback: MySQL unavailable (e.g. on Vercel or stopped XAMPP)
    $pdo = null;
    $db_connected = false;
}

/**
 * Helper to check DB status
 */
function isDbConnected() {
    global $db_connected, $pdo;
    return $db_connected && $pdo !== null;
}

/**
 * Return JSON response helper with proper headers & CORS
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
 * Portable HTTP helper (uses cURL if enabled, falls back to PHP stream context)
 */
function apolloHttpRequest($url, $method = 'GET', $headers = [], $body = null, $timeout = 5) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36');
        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $code, 'body' => $resp !== false ? $resp : ''];
    }

    $headerLines = array_merge(['User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36'], $headers);
    $httpOpts = [
        'method' => strtoupper($method),
        'header' => implode("\r\n", $headerLines) . "\r\n",
        'timeout' => $timeout,
        'ignore_errors' => true
    ];
    if ($body !== null) {
        $httpOpts['content'] = $body;
    }
    $ctx = stream_context_create([
        'http' => $httpOpts,
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        'socket' => ['bindto' => '0.0.0.0:0']
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    $respHeaders = function_exists('http_get_last_response_headers')
        ? (http_get_last_response_headers() ?: [])
        : ($http_response_header ?? []);
    $code = 0;
    foreach ($respHeaders as $hdrLine) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $hdrLine, $m)) {
            $code = (int)$m[1];
        }
    }
    return ['code' => $code, 'body' => $resp !== false ? $resp : ''];
}

/**
 * Get currently authenticated user from PHP session (or null if Guest)
 */
function apolloGetCurrentUser() {
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
        return [
            'id' => (int)$_SESSION['user_id'],
            'username' => (string)$_SESSION['username'],
            'email' => (string)($_SESSION['email'] ?? '')
        ];
    }
    return null;
}

/**
 * Get client IP address cleanly
 */
function apolloGetClientIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Parse User-Agent into readable Browser + OS summary
 */
function apolloParseBrowserDevice($ua = '') {
    if ($ua === '') {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';
    }
    $browser = 'Web Browser';
    if (stripos($ua, 'Edg/') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) $browser = 'Opera';
    elseif (stripos($ua, 'Firefox/') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Chrome/') !== false) $browser = 'Chrome';
    elseif (stripos($ua, 'Safari/') !== false) $browser = 'Safari';

    $os = 'Unknown OS';
    if (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
    elseif (stripos($ua, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Mac OS') !== false) $os = 'macOS';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';

    $summary = "{$browser} on {$os} — {$ua}";
    return substr($summary, 0, 250);
}

/**
 * Automatically log visitor opening/using the web app into visitor_logs
 */
function apolloTrackVisitor($pageUrl = '/') {
    global $pdo;
    if (!isDbConnected()) return;

    try {
        $user = apolloGetCurrentUser();
        $userId = $user ? $user['id'] : null;
        $username = $user ? $user['username'] : 'Guest';
        $sessId = session_id() ?: ('sess_' . md5(apolloGetClientIp() . ($_SERVER['HTTP_USER_AGENT'] ?? '')));
        $ip = apolloGetClientIp();
        $device = apolloParseBrowserDevice();

        // Update existing session visit within last 30 minutes, otherwise insert new visit row
        $chk = $pdo->prepare("
            SELECT id FROM visitor_logs
            WHERE session_id = ? AND last_active_at >= (NOW() - INTERVAL 30 MINUTE)
            ORDER BY id DESC LIMIT 1
        ");
        $chk->execute([$sessId]);
        $existing = $chk->fetch();

        if ($existing) {
            $upd = $pdo->prepare("
                UPDATE visitor_logs
                SET user_id = COALESCE(?, user_id),
                    username = CASE WHEN ? != 'Guest' THEN ? ELSE username END,
                    ip_address = ?,
                    page_url = ?,
                    last_active_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$userId, $username, $username, $ip, substr($pageUrl, 0, 250), $existing['id']]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO visitor_logs (user_id, username, session_id, ip_address, browser_device, page_url)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$userId, $username, $sessId, $ip, $device, substr($pageUrl, 0, 250)]);
        }
    } catch (Throwable $e) {
        // Silent fail if schema not yet migrated
    }
}

/**
 * Record user/visitor actions (PLAY_SONG, SEARCH, LIKE_SONG, LOGIN, REGISTER, etc.) into activity_logs
 */
function apolloLogActivity($actionType, $videoId = '', $trackTitle = '', $trackArtist = '', $detail = '', $overrideUser = null) {
    global $pdo;
    if (!isDbConnected()) return;

    try {
        $user = $overrideUser ?: apolloGetCurrentUser();
        $userId = $user ? (int)$user['id'] : null;
        $username = $user ? (string)$user['username'] : 'Guest';
        $ip = apolloGetClientIp();

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, username, action_type, video_id, track_title, track_artist, detail, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            substr($username, 0, 60),
            substr($actionType, 0, 40),
            substr((string)$videoId, 0, 50),
            substr((string)$trackTitle, 0, 255),
            substr((string)$trackArtist, 0, 150),
            substr((string)$detail, 0, 255),
            substr($ip, 0, 64)
        ]);
    } catch (Throwable $e) {
        // Silent fail so user experience is never interrupted
    }
}

// Load music filter and validation helpers
require_once __DIR__ . '/music_filter.php';
