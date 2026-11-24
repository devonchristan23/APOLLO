<?php
/**
 * Apollo Minimalist Music Player - Native PHP User Authentication API
 * Actions: status | register | login | logout
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['status' => 'ok']);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $parsed = json_decode($rawInput, true);
    if (is_array($parsed)) {
        $inputData = $parsed;
    }
}
$data = array_merge($_POST, $inputData);

if ($action === 'status') {
    $user = apolloGetCurrentUser();
    jsonResponse([
        'status' => 'ok',
        'db_connected' => isDbConnected(),
        'logged_in' => ($user !== null),
        'user' => $user
    ]);
}

if ($action === 'logout') {
    $prevUser = apolloGetCurrentUser();
    if ($prevUser) {
        apolloLogActivity('LOGOUT', '', '', '', 'User signed out', $prevUser);
    }
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
    jsonResponse([
        'status' => 'ok',
        'logged_in' => false,
        'message' => 'Logged out successfully'
    ]);
}

if (!isDbConnected()) {
    jsonResponse([
        'status' => 'error',
        'db_connected' => false,
        'message' => 'MySQL database is offline. Start XAMPP MySQL to use Register/Login.'
    ], 503);
}

try {
    switch ($action) {
        case 'register':
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

            // Check duplicate username or email
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
                'id' => $newUserId,
                'username' => $username,
                'email' => $email
            ];

            apolloTrackVisitor('/register');
            apolloLogActivity('REGISTER', '', '', '', "New account registered ({$email})", $userObj);

            jsonResponse([
                'status' => 'ok',
                'logged_in' => true,
                'message' => 'Registrasi berhasil!',
                'user' => $userObj
            ]);
            break;

        case 'login':
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
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'email' => $row['email']
            ];

            apolloTrackVisitor('/login');
            apolloLogActivity('LOGIN', '', '', '', 'User signed in', $userObj);

            jsonResponse([
                'status' => 'ok',
                'logged_in' => true,
                'message' => 'Login berhasil!',
                'user' => $userObj
            ]);
            break;

        default:
            jsonResponse(['status' => 'error', 'message' => 'Unknown auth action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse([
        'status' => 'error',
        'message' => 'Auth error: ' . $e->getMessage()
    ], 500);
}
