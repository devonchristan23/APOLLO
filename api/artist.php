<?php
/**
 * APOLLO — Endpoint API Profil & Diskografi Artis
 * Mengambil informasi artis beserta daftar lagu resminya berdasarkan parameter ?name=...
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/artist_service.php';

apolloCloseSessionWrite();

$name = trim($_GET['name'] ?? 'Queen');
$dossier = apolloBuildArtistDossier($name);

jsonResponse([
    'status'       => 'ok',
    'engine'       => 'youtube_music',
    'total_tracks' => count($dossier['tracks']),
    'artist'       => $dossier
]);
