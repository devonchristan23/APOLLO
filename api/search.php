<?php
/**
 * APOLLO — Endpoint API Pencarian & Rekomendasi Lagu
 * - GET api/search.php         -> Mengambil lagu Recommended dari YouTube Music (Home)
 * - GET api/search.php?q=oasis -> Mencari lagu resmi YouTube Music + YouTube secara paralel
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/recommendation_service.php';
require_once __DIR__ . '/../services/search_service.php';

$searchUser = apolloGetCurrentUser();
apolloCloseSessionWrite();

$query = trim($_GET['q'] ?? '');

// Mode 1: Tanpa kata kunci -> kembalikan rekomendasi lagu halaman Home
if ($query === '') {
    $liveRecs = apolloGetHomeRecommendations();
    jsonResponse([
        'status'             => 'ok',
        'is_recommendations' => true,
        'recommended_albums' => $liveRecs['albums'],
        'tracks'             => $liveRecs['tracks'],
        'artists'            => []
    ]);
}

// Mode 2: Pencarian lagu & artis berdasarkan kata kunci
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
    'query'            => $query,
    'total'            => count($searchResult['tracks']),
    'official_count'   => $searchResult['official_count'],
    'unofficial_count' => $searchResult['unofficial_count'],
    'engine'           => 'youtube_music+youtube',
    'tracks'           => $searchResult['tracks'],
    'artists'          => $searchResult['artists']
]);
