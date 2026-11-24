<?php
/**
 * Apollo Minimalist Music Player - Artist Dossier API
 * Priority: Local Project bin/yt-dlp -> Real YouTube Tracks
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$name = trim($_GET['name'] ?? 'Queen');
$key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));

// Helpers are loaded from config/db.php -> config/music_filter.php


// World-Famous Iconic Artist Dossier Metadata
$curatedMetadata = [
    'queen' => [
        'name' => 'QUEEN',
        'location' => 'LONDON, UK · TRIDENT STUDIOS',
        'desc' => 'Iconic British rock band formed in London in 1970 by Freddie Mercury, Brian May, Roger Taylor, and John Deacon. Renowned for their operatic anthems, progressive rock orchestration, and multi-layered vocal harmonies.',
        'photo' => 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg',
        'catalog_id' => 'QN-ARCH-1973-1991'
    ],
    'theweeknd' => [
        'name' => 'THE WEEKND',
        'location' => 'TORONTO, CA · XO RECORDS',
        'desc' => 'Canadian singer, songwriter, and record producer Abel Tesfaye. Celebrated for pioneering dark alternative R&B and retro 1980s synth-pop with international chart-topping master singles.',
        'photo' => 'https://i.ytimg.com/vi/4NRXx6U8ABQ/hqdefault.jpg',
        'catalog_id' => 'XO-ARCH-2011-2024'
    ],
    'daftpunk' => [
        'name' => 'DAFT PUNK',
        'location' => 'PARIS, FR · DAFT HOUSE',
        'desc' => 'French electronic music duo formed in 1993 by Guy-Manuel de Homem-Christo and Thomas Bangalter. Widely considered one of the most influential acts in dance music history, pioneering the French touch movement.',
        'photo' => 'https://i.ytimg.com/vi/5NV6Rdv1a3I/hqdefault.jpg',
        'catalog_id' => 'DP-ARCH-1997-2013'
    ],
    'radiohead' => [
        'name' => 'RADIOHEAD',
        'location' => 'OXFORDSHIRE, UK · COURTYARD STUDIO',
        'desc' => 'English rock band formed in Abingdon in 1985. Noted for their continuous experimental evolution from anthemic alternative rock to electronic glitch, jazz orchestration, and ambient textures.',
        'photo' => 'https://i.ytimg.com/vi/XFkzRNyygfk/hqdefault.jpg',
        'catalog_id' => 'RH-ARCH-1992-2016'
    ]
];

$meta = $curatedMetadata[$key] ?? [
    'name' => strtoupper($name),
    'location' => 'GLOBAL ARTIST CATALOG',
    'desc' => "Industrial audio archive and discography master recordings for " . htmlspecialchars($name) . ". Pure analog and high-fidelity signal master tape recordings.",
    'photo' => '',
    'catalog_id' => 'APOLLO-REF-' . strtoupper(substr(md5($name), 0, 8))
];

$artistTracks = [];
$firstThumb = '';

// =========================================================================
// PRIMARY: Fetch Real Songs using bin/yt-dlp --dump-json --flat-playlist "ytsearch25:{name} songs official audio"
// =========================================================================
$ytDlpBin = getYtDlpBinary();
$seenIds = [];

if (function_exists('exec') || function_exists('shell_exec')) {
    $searchTarget = "ytsearch25:{$name}";
    $cmd = escapeshellcmd($ytDlpBin) . " --dump-json --flat-playlist --no-warnings " . escapeshellarg($searchTarget) . " 2>/dev/null";
    
    $lines = [];
    @exec($cmd, $lines);

    if (!empty($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] !== '{') continue;
            $entry = json_decode($line, true);
            if (!is_array($entry) || empty($entry['id'])) continue;

            $vId = $entry['id'];
            if (isset($seenIds[$vId])) continue;

            $rawTitle = $entry['title'] ?? 'Master Audio Recording';
            $uploader = $entry['uploader'] ?? $entry['channel'] ?? '';
            $durationSec = (int)($entry['duration'] ?? 0);

            // STRICT FILTER: Eliminate compilations, full albums, reactions, podcasts, interviews, tutorials
            if (!isLegitimateMusicTrack($rawTitle, $durationSec, $uploader, $name)) {
                continue;
            }

            list($cleanTitle, $cleanArtist) = cleanMusicDetails($rawTitle, $uploader);
            $dur = formatSeconds($durationSec);

            $thumb = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
            if (!empty($entry['thumbnails']) && is_array($entry['thumbnails'])) {
                $lastThumb = end($entry['thumbnails']);
                if (!empty($lastThumb['url'])) {
                    $thumb = $lastThumb['url'];
                }
            }
            if (empty($firstThumb)) {
                $firstThumb = $thumb;
            }

            $seenIds[$vId] = true;
            $artistTracks[] = [
                'video_id' => $vId,
                'title' => $cleanTitle,
                'artist' => !empty($cleanArtist) ? $cleanArtist : $meta['name'],
                'album' => 'Essential Master Tape',
                'duration' => $dur,
                'thumbnail' => $thumb
            ];

            if (count($artistTracks) >= 20) {
                break;
            }
        }
    }
}

// Ensure photo exists
if (empty($meta['photo'])) {
    $meta['photo'] = !empty($firstThumb) ? $firstThumb : 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg';
}

$meta['tracks'] = $artistTracks;

jsonResponse([
    'status' => 'ok',
    'engine' => 'bin/yt-dlp',
    'total_tracks' => count($artistTracks),
    'artist' => $meta
]);
