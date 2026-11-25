<?php
/**
 * APOLLO — Endpoint API CRUD Playlist & Liked Songs
 * Mendukung penyimpanan MySQL (XAMPP) serta fallback otomatis ke localStorage browser.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/playlist_service.php';
require_once __DIR__ . '/../services/library_service.php';

apolloHandleCorsOptions();

$currentUser   = apolloGetCurrentUser();
$currentUserId = $currentUser ? (int)$currentUser['id'] : null;
apolloCloseSessionWrite();

if (!isDbConnected()) {
    jsonResponse([
        'status'       => 'fallback',
        'db_connected' => false,
        'message'      => 'MySQL database offline. Using client localStorage.'
    ]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data   = apolloReadRequestData();

try {
    switch ($action) {
        case 'get_playlists':
            jsonResponse([
                'status'       => 'ok',
                'db_connected' => true,
                'user'         => $currentUser,
                'playlists'    => apolloListPlaylists($pdo, $currentUserId)
            ]);
            break;

        case 'get_playlist_tracks':
            $playlistId = (int)($_GET['playlist_id'] ?? $data['playlist_id'] ?? 0);
            if ($playlistId <= 0) {
                jsonResponse(['status' => 'error', 'message' => 'Invalid playlist ID'], 400);
            }
            if (!apolloFindOwnedPlaylist($pdo, $playlistId, $currentUserId)) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
            }
            jsonResponse([
                'status'      => 'ok',
                'playlist_id' => $playlistId,
                'tracks'      => apolloGetTracksInPlaylist($pdo, $playlistId)
            ]);
            break;

        case 'create_playlist':
            $name = trim($data['name'] ?? '');
            $desc = trim($data['description'] ?? '');
            if ($name === '') {
                jsonResponse(['status' => 'error', 'message' => 'Playlist name is required'], 400);
            }
            jsonResponse([
                'status'   => 'ok',
                'message'  => 'Playlist created successfully',
                'playlist' => apolloCreatePlaylistRecord($pdo, $currentUserId, $name, $desc, $currentUser)
            ]);
            break;

        case 'delete_playlist':
            $id = (int)($data['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['status' => 'error', 'message' => 'Invalid playlist ID'], 400);
            }
            $plRow = apolloFindOwnedPlaylist($pdo, $id, $currentUserId);
            if (!$plRow) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
            }
            if (strtolower(trim($plRow['name'])) === 'liked songs') {
                jsonResponse(['status' => 'error', 'message' => 'Liked Songs playlist cannot be deleted'], 403);
            }
            $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ?");
            $stmt->execute([$id]);
            apolloLogActivity('DELETE_PLAYLIST', '', '', '', "Deleted playlist: {$plRow['name']}", $currentUser);
            jsonResponse(['status' => 'ok', 'message' => 'Playlist deleted successfully', 'id' => $id]);
            break;

        case 'add_track':
            $playlistId = (int)($data['playlist_id'] ?? 0);
            $videoId    = trim($data['video_id'] ?? '');
            $title      = trim($data['title'] ?? '');
            if ($playlistId <= 0 || $videoId === '' || $title === '') {
                jsonResponse(['status' => 'error', 'message' => 'Missing required track fields'], 400);
            }
            $plRow = apolloFindOwnedPlaylist($pdo, $playlistId, $currentUserId);
            if (!$plRow) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
            }
            $saved = apolloSaveTrackToPlaylist($pdo, $plRow, [
                'video_id'  => $videoId,
                'title'     => $title,
                'artist'    => trim($data['artist'] ?? 'Unknown Artist'),
                'album'     => trim($data['album'] ?? ''),
                'duration'  => trim($data['duration'] ?? '00:00'),
                'thumbnail' => trim($data['thumbnail'] ?? '')
            ], $currentUser);

            if ($saved['exists']) {
                jsonResponse(['status' => 'ok', 'message' => 'Track already in playlist', 'exists' => true]);
            }
            jsonResponse(['status' => 'ok', 'message' => 'Track added to playlist', 'track' => $saved['track']]);
            break;

        case 'remove_track':
            $trackId    = (int)($data['track_id'] ?? $data['id'] ?? 0);
            $playlistId = (int)($data['playlist_id'] ?? 0);
            $videoId    = trim($data['video_id'] ?? '');

            if ($playlistId > 0 && !apolloFindOwnedPlaylist($pdo, $playlistId, $currentUserId)) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
            }
            if ($trackId > 0) {
                $stmt = $pdo->prepare("DELETE FROM saved_tracks WHERE id = ?");
                $stmt->execute([$trackId]);
            } elseif ($playlistId > 0 && $videoId !== '') {
                $stmt = $pdo->prepare("DELETE FROM saved_tracks WHERE playlist_id = ? AND video_id = ?");
                $stmt->execute([$playlistId, $videoId]);
            } else {
                jsonResponse(['status' => 'error', 'message' => 'Invalid parameters for track removal'], 400);
            }
            jsonResponse(['status' => 'ok', 'message' => 'Track removed successfully']);
            break;

        case 'get_library_collections':
            jsonResponse([
                'status'           => 'ok',
                'db_connected'     => true,
                'followed_artists' => apolloListFollowedArtists($pdo, $currentUserId),
                'saved_albums'     => apolloListSavedAlbums($pdo, $currentUserId)
            ]);
            break;

        case 'toggle_follow_artist':
            $res = apolloToggleFollowArtist($pdo, $currentUserId, $data, $currentUser);
            jsonResponse([
                'status'    => 'ok',
                'following' => $res['following'],
                'artist'    => $res['artist']
            ]);
            break;

        case 'toggle_save_album':
            $res = apolloToggleSaveAlbum($pdo, $currentUserId, $data, $currentUser);
            jsonResponse([
                'status' => 'ok',
                'saved'  => $res['saved'],
                'album'  => $res['album']
            ]);
            break;

        default:
            jsonResponse(['status' => 'error', 'message' => 'Unknown action: ' . $action], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()], 500);
}
