<?php
/**
 * APOLLO — Endpoint API Pencarian Multi-Tab & Rekomendasi Lagu
 * - GET api/search.php                       -> Rekomendasi lagu YouTube Music (Home)
 * - GET api/search.php?refresh=1             -> Refresh paksa rekomendasi baru
 * - GET api/search.php?q=oasis&type=tracks   -> Tab Tracks / Songs (YT Music + Video YouTube)
 * - GET api/search.php?q=oasis&type=albums   -> Tab Albums (Album resmi band/artis)
 * - GET api/search.php?q=oasis&type=artists  -> Tab Artists (Artis yang cocok)
 * - GET api/search.php?q=oasis&type=playlists-> Tab Playlists (Playlist komunitas)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/recommendation_service.php';
require_once __DIR__ . '/../services/search_service.php';

$searchUser = apolloGetCurrentUser();
apolloCloseSessionWrite();

$query        = trim($_GET['q'] ?? '');
$type         = strtolower(trim($_GET['type'] ?? 'tracks'));
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

// Mode 1: Tanpa kata kunci -> kembalikan rekomendasi lagu halaman Home
if ($query === '') {
    $liveRecs = apolloGetHomeRecommendations($forceRefresh);
    jsonResponse([
        'status'             => 'ok',
        'is_recommendations' => true,
        'recommended_albums' => $liveRecs['albums'],
        'tracks'             => $liveRecs['tracks'],
        'artists'            => []
    ]);
}

// Mode 2: Tab Albums (Album Resmi Band / Artis)
if ($type === 'albums') {
    $albums = apolloSearchAlbums($query, 16);
    jsonResponse([
        'status' => 'ok',
        'type'   => 'albums',
        'query'  => $query,
        'total'  => count($albums),
        'albums' => $albums
    ]);
}

// Mode 3: Tab Artists (Daftar Artis yang Sesuai Hasil Pencarian)
if ($type === 'artists') {
    $artists = apolloSearchArtists($query, 16);
    jsonResponse([
        'status'  => 'ok',
        'type'    => 'artists',
        'query'   => $query,
        'total'   => count($artists),
        'artists' => $artists
    ]);
}

// Mode 4: Tab Playlists (Playlist dari Komunitas YouTube Music)
if ($type === 'playlists') {
    $playlists = apolloSearchCommunityPlaylists($query, 16);
    jsonResponse([
        'status'    => 'ok',
        'type'      => 'playlists',
        'query'     => $query,
        'total'     => count($playlists),
        'playlists' => $playlists
    ]);
}

// Mode Default: Tab Tracks / Songs
$searchResult = apolloSearchTracksAndArtists($query);

apolloLogActivity(
    'SEARCH',
    '',
    '',
    '',
    "Query: {$query} (" . count($searchResult['tracks']) . " results)",
    $searchUser
);

jsonResponse([
    'status'           => 'ok',
    'type'             => 'tracks',
    'query'            => $query,
    'total'            => count($searchResult['tracks']),
    'official_count'   => $searchResult['official_count'],
    'unofficial_count' => $searchResult['unofficial_count'],
    'engine'           => 'youtube_music+youtube',
    'tracks'           => $searchResult['tracks']
]);
