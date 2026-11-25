<?php
/**
 * APOLLO — Service Database Playlist & Liked Songs (PDO MySQL)
 * Memisahkan seluruh query SQL CRUD playlist dari file endpoint api/playlist.php.
 */

/**
 * Pastikan playlist benar-benar milik user yang sedang aktif (atau Guest jika belum login)
 */
function apolloFindOwnedPlaylist($pdo, $playlistId, $currentUserId) {
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("SELECT id, user_id, name, description FROM playlists WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$playlistId, $currentUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, user_id, name, description FROM playlists WHERE id = ? AND user_id IS NULL LIMIT 1");
        $stmt->execute([$playlistId]);
    }
    return $stmt->fetch();
}

/**
 * Ambil seluruh playlist milik user/guest beserta jumlah lagu & cover terbaru
 */
function apolloListPlaylists($pdo, $currentUserId) {
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.user_id, p.name, p.description, p.created_at,
                   COUNT(t.id) AS track_count,
                   (SELECT t2.thumbnail FROM saved_tracks t2 WHERE t2.playlist_id = p.id AND t2.thumbnail != '' ORDER BY t2.id DESC LIMIT 1) AS cover_thumb
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
                   COUNT(t.id) AS track_count,
                   (SELECT t2.thumbnail FROM saved_tracks t2 WHERE t2.playlist_id = p.id AND t2.thumbnail != '' ORDER BY t2.id DESC LIMIT 1) AS cover_thumb
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

    return $playlists;
}

/**
 * Ambil semua lagu tersimpan di dalam satu playlist
 */
function apolloGetTracksInPlaylist($pdo, $playlistId) {
    $stmt = $pdo->prepare("
        SELECT id, playlist_id, video_id, title, artist, album, duration, thumbnail, added_at
        FROM saved_tracks
        WHERE playlist_id = ?
        ORDER BY id ASC
    ");
    $stmt->execute([$playlistId]);
    return $stmt->fetchAll();
}

/**
 * Buat playlist baru (mencegah duplikasi playlist permanen "Liked Songs")
 */
function apolloCreatePlaylistRecord($pdo, $currentUserId, $name, $desc, $currentUser) {
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
            return $existingLiked;
        }
        $name = 'Liked Songs';
        if ($desc === '') $desc = 'Permanent collection of liked songs';
    }

    $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name, description) VALUES (?, ?, ?)");
    $stmt->execute([$currentUserId, $name, $desc]);
    $newId = (int)$pdo->lastInsertId();

    if (strtolower($name) !== 'liked songs') {
        apolloLogActivity('CREATE_PLAYLIST', '', '', '', "Created playlist: {$name}", $currentUser);
    }

    return [
        'id'          => $newId,
        'user_id'     => $currentUserId,
        'name'        => $name,
        'description' => $desc,
        'track_count' => 0,
        'cover_thumb' => null,
        'is_liked'    => (strtolower($name) === 'liked songs')
    ];
}

/**
 * Tambahkan lagu ke dalam playlist (jika belum ada)
 */
function apolloSaveTrackToPlaylist($pdo, $plRow, $trackData, $currentUser) {
    $playlistId = (int)$plRow['id'];
    $videoId    = $trackData['video_id'];
    $title      = $trackData['title'];
    $artist     = $trackData['artist'];
    $album      = $trackData['album'];
    $duration   = $trackData['duration'];
    $thumbnail  = $trackData['thumbnail'];

    $check = $pdo->prepare("SELECT id FROM saved_tracks WHERE playlist_id = ? AND video_id = ?");
    $check->execute([$playlistId, $videoId]);
    if ($check->fetch()) {
        return ['exists' => true, 'track' => null];
    }

    $stmt = $pdo->prepare("
        INSERT INTO saved_tracks (playlist_id, video_id, title, artist, album, duration, thumbnail)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$playlistId, $videoId, $title, $artist, $album, $duration, $thumbnail]);
    $insertedId = (int)$pdo->lastInsertId();

    $actType = (strtolower(trim($plRow['name'])) === 'liked songs') ? 'LIKE_SONG' : 'SAVE_TRACK';
    apolloLogActivity($actType, $videoId, $title, $artist, "Playlist: {$plRow['name']}", $currentUser);

    return [
        'exists' => false,
        'track'  => [
            'id'          => $insertedId,
            'playlist_id' => $playlistId,
            'video_id'    => $videoId,
            'title'       => $title,
            'artist'      => $artist,
            'album'       => $album,
            'duration'    => $duration,
            'thumbnail'   => $thumbnail
        ]
    ];
}
