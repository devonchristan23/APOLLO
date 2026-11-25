<?php
/**
 * APOLLO — Service Koleksi Library (Followed Artists & Saved Official Albums)
 * Mengelola tabel MySQL followed_artists dan saved_albums per-user/guest secara otomatis.
 */

function apolloEnsureLibraryTables($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `followed_artists` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT DEFAULT NULL,
          `artist_name` VARCHAR(150) NOT NULL,
          `browse_id` VARCHAR(100) DEFAULT '',
          `photo` VARCHAR(500) DEFAULT '',
          `subtitle` VARCHAR(200) DEFAULT '',
          `followed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_follow_user` (`user_id`),
          CONSTRAINT `fk_follow_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `saved_albums` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT DEFAULT NULL,
          `browse_id` VARCHAR(120) NOT NULL,
          `title` VARCHAR(200) NOT NULL,
          `artist` VARCHAR(150) DEFAULT '',
          `year` VARCHAR(20) DEFAULT '',
          `artwork` VARCHAR(500) DEFAULT '',
          `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_saved_album_user` (`user_id`),
          CONSTRAINT `fk_saved_album_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function apolloListFollowedArtists($pdo, $currentUserId) {
    apolloEnsureLibraryTables($pdo);
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("SELECT id, artist_name AS name, browse_id, photo, subtitle, followed_at FROM followed_artists WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$currentUserId]);
    } else {
        $stmt = $pdo->query("SELECT id, artist_name AS name, browse_id, photo, subtitle, followed_at FROM followed_artists WHERE user_id IS NULL ORDER BY id DESC");
    }
    return $stmt->fetchAll();
}

function apolloToggleFollowArtist($pdo, $currentUserId, $data, $currentUser) {
    apolloEnsureLibraryTables($pdo);
    $artistName = trim($data['name'] ?? $data['artist_name'] ?? '');
    if ($artistName === '') {
        jsonResponse(['status' => 'error', 'message' => 'Artist name is required'], 400);
    }

    if ($currentUserId !== null) {
        $chk = $pdo->prepare("SELECT id FROM followed_artists WHERE user_id = ? AND LOWER(TRIM(artist_name)) = LOWER(?) LIMIT 1");
        $chk->execute([$currentUserId, $artistName]);
    } else {
        $chk = $pdo->prepare("SELECT id FROM followed_artists WHERE user_id IS NULL AND LOWER(TRIM(artist_name)) = LOWER(?) LIMIT 1");
        $chk->execute([$artistName]);
    }
    $existing = $chk->fetch();

    if ($existing) {
        $del = $pdo->prepare("DELETE FROM followed_artists WHERE id = ?");
        $del->execute([$existing['id']]);
        apolloLogActivity('UNFOLLOW_ARTIST', '', '', $artistName, 'Removed from Library', $currentUser);
        return ['following' => false, 'artist' => null];
    }

    $browseId = trim($data['browse_id'] ?? '');
    $photo    = trim($data['photo'] ?? '');
    $subtitle = trim($data['subtitle'] ?? 'Artist');

    $ins = $pdo->prepare("INSERT INTO followed_artists (user_id, artist_name, browse_id, photo, subtitle) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$currentUserId, $artistName, $browseId, $photo, $subtitle]);
    $newId = (int)$pdo->lastInsertId();

    apolloLogActivity('FOLLOW_ARTIST', '', '', $artistName, 'Saved to Library', $currentUser);
    return [
        'following' => true,
        'artist' => [
            'id'        => $newId,
            'name'      => $artistName,
            'browse_id' => $browseId,
            'photo'     => $photo,
            'subtitle'  => $subtitle
        ]
    ];
}

function apolloListSavedAlbums($pdo, $currentUserId) {
    apolloEnsureLibraryTables($pdo);
    if ($currentUserId !== null) {
        $stmt = $pdo->prepare("SELECT id, browse_id, title, artist, year, artwork, saved_at FROM saved_albums WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$currentUserId]);
    } else {
        $stmt = $pdo->query("SELECT id, browse_id, title, artist, year, artwork, saved_at FROM saved_albums WHERE user_id IS NULL ORDER BY id DESC");
    }
    return $stmt->fetchAll();
}

function apolloToggleSaveAlbum($pdo, $currentUserId, $data, $currentUser) {
    apolloEnsureLibraryTables($pdo);
    $browseId = trim($data['browse_id'] ?? '');
    $title    = trim($data['title'] ?? '');
    if ($browseId === '' || $title === '') {
        jsonResponse(['status' => 'error', 'message' => 'Album browse_id and title are required'], 400);
    }

    if ($currentUserId !== null) {
        $chk = $pdo->prepare("SELECT id FROM saved_albums WHERE user_id = ? AND browse_id = ? LIMIT 1");
        $chk->execute([$currentUserId, $browseId]);
    } else {
        $chk = $pdo->prepare("SELECT id FROM saved_albums WHERE user_id IS NULL AND browse_id = ? LIMIT 1");
        $chk->execute([$browseId]);
    }
    $existing = $chk->fetch();

    if ($existing) {
        $del = $pdo->prepare("DELETE FROM saved_albums WHERE id = ?");
        $del->execute([$existing['id']]);
        apolloLogActivity('UNSAVE_ALBUM', '', $title, trim($data['artist'] ?? ''), 'Removed album from Library', $currentUser);
        return ['saved' => false, 'album' => null];
    }

    $artist  = trim($data['artist'] ?? '');
    $year    = trim($data['year'] ?? '');
    $artwork = trim($data['artwork'] ?? '');

    $ins = $pdo->prepare("INSERT INTO saved_albums (user_id, browse_id, title, artist, year, artwork) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->execute([$currentUserId, $browseId, $title, $artist, $year, $artwork]);
    $newId = (int)$pdo->lastInsertId();

    apolloLogActivity('SAVE_ALBUM', '', $title, $artist, 'Saved album to Library', $currentUser);
    return [
        'saved' => true,
        'album' => [
            'id'        => $newId,
            'browse_id' => $browseId,
            'title'     => $title,
            'artist'    => $artist,
            'year'      => $year,
            'artwork'   => $artwork
        ]
    ];
}
