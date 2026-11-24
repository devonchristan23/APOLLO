<?php
/**
 * Apollo Minimalist Music Player - Playlist CRUD API (Scoped per User Account + Guest)
 * Hybrid storage: MySQL XAMPP with fallback response for LocalStorage
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['status' => 'ok']);
}

$currentUser = apolloGetCurrentUser();
$currentUserId = $currentUser ? (int)$currentUser['id'] : null;

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if (!isDbConnected()) {
    jsonResponse([
        'status' => 'fallback',
        'db_connected' => false,
        'message' => 'MySQL database offline. Using client localStorage.'
    ]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $parsed = json_decode($rawInput, true);
    if (is_array($parsed)) {
        $inputData = $parsed;
    }
}
$data = array_merge($_POST, $inputData);

/**
 * Verify that a playlist belongs to the current user (or Guest if not logged in)
 */
function findOwnedPlaylist($pdo, $playlistId, $currentUserId) {
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("SELECT id, user_id, name, description FROM playlists WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$playlistId, $currentUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, user_id, name, description FROM playlists WHERE id = ? AND user_id IS NULL LIMIT 1");
        $stmt->execute([$playlistId]);
    }
    return $stmt->fetch();
}

try {
    switch ($action) {
        case 'get_playlists':
            if ($currentUserId !== null) {
                $stmt = $pdo->prepare("
                    SELECT p.id, p.user_id, p.name, p.description, p.created_at,
                           COUNT(t.id) as track_count,
                           (SELECT t2.thumbnail FROM saved_tracks t2 WHERE t2.playlist_id = p.id AND t2.thumbnail != '' ORDER BY t2.id DESC LIMIT 1) as cover_thumb
                    FROM playlists p
                    LEFT JOIN saved_tracks t ON p.id = t.playlist_id
                    WHERE p.user_id = ?
                    GROUP BY p.id, p.user_id, p.name, p.description, p.created_at
                    ORDER BY CASE WHEN LOWER(TRIM(p.name)) = 'liked songs' THEN 0 ELSE 1 END, p.id ASC
                ");
                $stmt->execute([$currentUserId]);
            } else {
                $stmt = $pdo->query("
                    SELECT p.id, p.user_id, p.name, p.description, p.created_at,
                           COUNT(t.id) as track_count,
                           (SELECT t2.thumbnail FROM saved_tracks t2 WHERE t2.playlist_id = p.id AND t2.thumbnail != '' ORDER BY t2.id DESC LIMIT 1) as cover_thumb
                    FROM playlists p
                    LEFT JOIN saved_tracks t ON p.id = t.playlist_id
                    WHERE p.user_id IS NULL
                    GROUP BY p.id, p.user_id, p.name, p.description, p.created_at
                    ORDER BY CASE WHEN LOWER(TRIM(p.name)) = 'liked songs' THEN 0 ELSE 1 END, p.id ASC
                ");
            }
            $playlists = $stmt->fetchAll();
            foreach ($playlists as &$pl) {
                $pl['is_liked'] = (strtolower(trim($pl['name'])) === 'liked songs');
            }
            unset($pl);
            jsonResponse([
                'status' => 'ok',
                'db_connected' => true,
                'user' => $currentUser,
                'playlists' => $playlists
            ]);
            break;

        case 'get_playlist_tracks':
            $playlistId = intval($_GET['playlist_id'] ?? $data['playlist_id'] ?? 0);
            if ($playlistId <= 0) {
                jsonResponse(['status' => 'error', 'message' => 'Invalid playlist ID'], 400);
            }
            $owned = findOwnedPlaylist($pdo, $playlistId, $currentUserId);
            if (!$owned) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
            }
            $stmt = $pdo->prepare("
                SELECT id, playlist_id, video_id, title, artist, album, duration, thumbnail, added_at
                FROM saved_tracks
                WHERE playlist_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$playlistId]);
            $tracks = $stmt->fetchAll();
            jsonResponse([
                'status' => 'ok',
                'playlist_id' => $playlistId,
                'tracks' => $tracks
            ]);
            break;

        case 'create_playlist':
            $name = trim($data['name'] ?? '');
            $desc = trim($data['description'] ?? '');
            if (empty($name)) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist name is required'], 400);
            }

            // Prevent duplicate "Liked Songs" playlist within same user/guest scope
            if (strtolower($name) === 'liked songs') {
                if ($currentUserId !== null) {
                    $chk = $pdo->prepare("SELECT id, name, description FROM playlists WHERE user_id = ? AND LOWER(TRIM(name)) = 'liked songs' LIMIT 1");
                    $chk->execute([$currentUserId]);
                } else {
                    $chk = $pdo->query("SELECT id, name, description FROM playlists WHERE user_id IS NULL AND LOWER(TRIM(name)) = 'liked songs' LIMIT 1");
                }
                $existingLiked = $chk->fetch();
                if ($existingLiked) {
                    $existingLiked['is_liked'] = true;
                    $existingLiked['track_count'] = 0;
                    jsonResponse([
                        'status' => 'ok',
                        'message' => 'Liked Songs playlist ready',
                        'playlist' => $existingLiked
                    ]);
                }
                $name = 'Liked Songs';
                if (empty($desc)) $desc = 'Permanent collection of liked songs';
            }

            $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$currentUserId, $name, $desc]);
            $newId = (int)$pdo->lastInsertId();

            if (strtolower($name) !== 'liked songs') {
                apolloLogActivity('CREATE_PLAYLIST', '', '', '', "Created playlist: {$name}", $currentUser);
            }

            jsonResponse([
                'status' => 'ok',
                'message' => 'Playlist created successfully',
                'playlist' => [
                    'id' => $newId,
                    'user_id' => $currentUserId,
                    'name' => $name,
                    'description' => $desc,
                    'track_count' => 0,
                    'cover_thumb' => null,
                    'is_liked' => (strtolower($name) === 'liked songs')
                ]
            ]);
            break;

        case 'delete_playlist':
            $id = intval($data['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['status' => 'error', 'message' => 'Invalid playlist ID'], 400);
            }
            $plRow = findOwnedPlaylist($pdo, $id, $currentUserId);
            if (!$plRow) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
            }
            if (strtolower(trim($plRow['name'])) === 'liked songs') {
                jsonResponse(['status' => 'error', 'message' => 'Liked Songs playlist cannot be deleted'], 403);
            }
            $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ?");
            $stmt->execute([$id]);

            apolloLogActivity('DELETE_PLAYLIST', '', '', '', "Deleted playlist: {$plRow['name']}", $currentUser);

            jsonResponse([
                'status' => 'ok',
                'message' => 'Playlist deleted successfully',
                'id' => $id
            ]);
            break;

        case 'add_track':
            $playlistId = intval($data['playlist_id'] ?? 0);
            $videoId = trim($data['video_id'] ?? '');
            $title = trim($data['title'] ?? '');
            $artist = trim($data['artist'] ?? 'Unknown Artist');
            $album = trim($data['album'] ?? '');
            $duration = trim($data['duration'] ?? '00:00');
            $thumbnail = trim($data['thumbnail'] ?? '');

            if ($playlistId <= 0 || empty($videoId) || empty($title)) {
                jsonResponse(['status' => 'error', 'message' => 'Missing required track fields'], 400);
            }

            $plRow = findOwnedPlaylist($pdo, $playlistId, $currentUserId);
            if (!$plRow) {
                jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
            }

            $check = $pdo->prepare("SELECT id FROM saved_tracks WHERE playlist_id = ? AND video_id = ?");
            $check->execute([$playlistId, $videoId]);
            if ($check->fetch()) {
                jsonResponse([
                    'status' => 'ok',
                    'message' => 'Track already in playlist',
                    'exists' => true
                ]);
            }

            $stmt = $pdo->prepare("
                INSERT INTO saved_tracks (playlist_id, video_id, title, artist, album, duration, thumbnail)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$playlistId, $videoId, $title, $artist, $album, $duration, $thumbnail]);
            $insertedId = (int)$pdo->lastInsertId();

            $actType = (strtolower(trim($plRow['name'])) === 'liked songs') ? 'LIKE_SONG' : 'SAVE_TRACK';
            apolloLogActivity($actType, $videoId, $title, $artist, "Playlist: {$plRow['name']}", $currentUser);

            jsonResponse([
                'status' => 'ok',
                'message' => 'Track added to playlist',
                'track' => [
                    'id' => $insertedId,
                    'playlist_id' => $playlistId,
                    'video_id' => $videoId,
                    'title' => $title,
                    'artist' => $artist,
                    'album' => $album,
                    'duration' => $duration,
                    'thumbnail' => $thumbnail
                ]
            ]);
            break;

        case 'remove_track':
            $trackId = intval($data['track_id'] ?? $data['id'] ?? 0);
            $playlistId = intval($data['playlist_id'] ?? 0);
            $videoId = trim($data['video_id'] ?? '');

            if ($playlistId > 0) {
                $plRow = findOwnedPlaylist($pdo, $playlistId, $currentUserId);
                if (!$plRow) {
                    jsonResponse(['status' => 'error', 'message' => 'Playlist not found'], 404);
                }
            }

            if ($trackId > 0) {
                $stmt = $pdo->prepare("DELETE FROM saved_tracks WHERE id = ?");
                $stmt->execute([$trackId]);
            } elseif ($playlistId > 0 && !empty($videoId)) {
                $stmt = $pdo->prepare("DELETE FROM saved_tracks WHERE playlist_id = ? AND video_id = ?");
                $stmt->execute([$playlistId, $videoId]);
            } else {
                jsonResponse(['status' => 'error', 'message' => 'Invalid parameters for track removal'], 400);
            }

            jsonResponse([
                'status' => 'ok',
                'message' => 'Track removed successfully'
            ]);
            break;

        default:
            jsonResponse(['status' => 'error', 'message' => 'Unknown action: ' . $action], 400);
    }
} catch (Throwable $e) {
    jsonResponse([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ], 500);
}
