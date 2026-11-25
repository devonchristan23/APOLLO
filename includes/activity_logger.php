<?php
/**
 * APOLLO — Pencatat Pengunjung (Visitor Logs) & Aktivitas User (Activity Logs)
 * Menyimpan riwayat kunjungan dan aksi lagu ke tabel MySQL jika database aktif.
 */

/**
 * Ambil alamat IP pengunjung dengan aman
 */
function apolloGetClientIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Ringkas informasi Browser & OS dari header User-Agent
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

    return substr("{$browser} on {$os} — {$ua}", 0, 250);
}

/**
 * Catat kunjungan ke tabel visitor_logs (update jika sesi masih aktif dalam 30 menit)
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
        // Jangan hentikan aplikasi jika tabel belum dimigrasi
    }
}

/**
 * Catat aktivitas user (PLAY_SONG, SEARCH, LIKE_SONG, LOGIN, REGISTER, dll.)
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
        // Silent fail agar pemutaran musik tetap lancar
    }
}
