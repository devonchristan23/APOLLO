<?php
/**
 * Apollo Minimalist Music Player - Visitor & Playback Activity Logger API (Full PHP Native)
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['status' => 'ok']);
}

$currentUser = apolloGetCurrentUser();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if (!isDbConnected()) {
    jsonResponse(['status' => 'offline', 'db_connected' => false]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'log_play';

$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $parsed = json_decode($rawInput, true);
    if (is_array($parsed)) {
        $inputData = $parsed;
    }
}
$data = array_merge($_POST, $inputData);

if ($action === 'log_play') {
    $videoId = trim($data['video_id'] ?? '');
    $title = trim($data['title'] ?? '');
    $artist = trim($data['artist'] ?? '');
    $album = trim($data['album'] ?? '');

    if ($videoId !== '' && $title !== '') {
        apolloLogActivity('PLAY_SONG', $videoId, $title, $artist, $album, $currentUser);
    }

    jsonResponse(['status' => 'ok']);
}

jsonResponse(['status' => 'ok']);
