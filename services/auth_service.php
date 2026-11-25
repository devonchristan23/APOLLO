<?php
/**
 * APOLLO — Service Autentikasi User (Register, Login, & Logout)
 * Mengelola validasi akun, password hashing, dan penyimpanan PHP Session.
 */

/**
 * Proses logout user dan bersihkan PHP Session
 */
function apolloLogoutSession() {
    $prevUser = apolloGetCurrentUser();
    if ($prevUser) {
        apolloLogActivity('LOGOUT', '', '', '', 'User signed out', $prevUser);
    }
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
}

/**
 * Validasi & daftarkan akun baru ke tabel users MySQL
 */
function apolloRegisterAccount($pdo, $data) {
    $username = trim($data['username'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if (strlen($username) < 3 || strlen($username) > 50) {
        jsonResponse(['status' => 'error', 'message' => 'Username minimal 3 karakter (maksimal 50).'], 400);
    }
    if (!preg_match('/^[a-zA-Z0-9_.\-\s]+$/', $username)) {
        jsonResponse(['status' => 'error', 'message' => 'Username hanya boleh huruf, angka, spasi, titik, atau underscore.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['status' => 'error', 'message' => 'Format email tidak valid.'], 400);
    }
    if (strlen($password) < 4) {
        jsonResponse(['status' => 'error', 'message' => 'Password minimal 4 karakter.'], 400);
    }

    // Cek duplikasi username atau email
    $chk = $pdo->prepare("SELECT id, username, email FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
    $chk->execute([$username, $email]);
    $dup = $chk->fetch();
    if ($dup) {
        if (strtolower($dup['username']) === strtolower($username)) {
            jsonResponse(['status' => 'error', 'message' => 'Username sudah dipakai, silakan pilih nama lain.'], 409);
        }
        jsonResponse(['status' => 'error', 'message' => 'Email sudah terdaftar, silakan langsung Login.'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash, last_login_at) VALUES (?, ?, ?, NOW())");
    $ins->execute([$username, $email, $hash]);
    $newUserId = (int)$pdo->lastInsertId();

    $_SESSION['user_id'] = $newUserId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    $userObj = [
        'id'       => $newUserId,
        'username' => $username,
        'email'    => $email
    ];

    apolloTrackVisitor('/register');
    apolloLogActivity('REGISTER', '', '', '', "New account registered ({$email})", $userObj);

    return $userObj;
}

/**
 * Validasi kredensial login (username atau email) dan aktifkan PHP Session
 */
function apolloAuthenticateAccount($pdo, $data) {
    $identity = trim($data['identity'] ?? $data['username'] ?? $data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    if ($identity === '' || $password === '') {
        jsonResponse(['status' => 'error', 'message' => 'Masukkan username/email dan password.'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->execute([$identity, $identity]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        jsonResponse(['status' => 'error', 'message' => 'Username/email atau password salah.'], 401);
    }

    $upd = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $upd->execute([$row['id']]);

    $_SESSION['user_id'] = (int)$row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['email'] = $row['email'];

    $userObj = [
        'id'       => (int)$row['id'],
        'username' => $row['username'],
        'email'    => $row['email']
    ];

    apolloTrackVisitor('/login');
    apolloLogActivity('LOGIN', '', '', '', 'User signed in', $userObj);

    return $userObj;
}
