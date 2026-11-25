<?php
/**
 * APOLLO — Endpoint API Pencatat Aktivitas Pemutaran Lagu
 * Mencatat lagu yang sedang diputar user ke tabel activity_logs MySQL.
 */

require_once __DIR__ . '/../config/db.php';

apolloHandleCorsOptions();

$currentUser = apolloGetCurrentUser();
apolloCloseSessionWrite();

if (!isDbConnected()) {
    jsonResponse(['status' => 'offline', 'db_connected' => false]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'log_play';
$data   = apolloReadRequestData();

if ($action === 'log_play') {
    $videoId = trim($data['video_id'] ?? '');
    $title   = trim($data['title'] ?? '');
    $artist  = trim($data['artist'] ?? '');
    $album   = trim($data['album'] ?? '');

    if ($videoId !== '' && $title !== '') {
        apolloLogActivity('PLAY_SONG', $videoId, $title, $artist, $album, $currentUser);
    }
}

jsonResponse(['status' => 'ok']);
