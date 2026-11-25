<?php
/**
 * APOLLO — Endpoint API Community Albums & Playlists (YouTube Music)
 * - action=list   : Daftar 4 playlist komunitas populer
 * - action=tracks : Daftar lagu di dalam satu playlist komunitas (browse_id)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/community_service.php';

apolloCloseSessionWrite();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    jsonResponse([
        'status'    => 'ok',
        'playlists' => apolloGetCommunityPlaylists()
    ]);
}

if ($action === 'tracks') {
    $browseId = trim($_GET['browse_id'] ?? '');
    if ($browseId === '' || !preg_match('/^[a-zA-Z0-9_\-]+$/', $browseId)) {
        jsonResponse(['status' => 'error', 'message' => 'Invalid browse_id'], 400);
    }

    jsonResponse([
        'status'    => 'ok',
        'browse_id' => $browseId,
        'tracks'    => apolloGetCommunityPlaylistTracks($browseId)
    ]);
}

jsonResponse(['status' => 'error', 'message' => 'Unknown action'], 400);
