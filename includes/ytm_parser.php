<?php
/**
 * APOLLO — Parser & Formatter Data YouTube Music (Innertube API)
 * Berisi fungsi bantu untuk mem-parsing durasi, upgrade resolusi cover 544x544,
 * serta mengubah item JSON YouTube Music menjadi struktur lagu standar APOLLO.
 */

/**
 * Buat payload context standar untuk request Innertube YouTube Music / Web
 */
function apolloBuildInnertubeContext($clientName = 'WEB_REMIX', $clientVersion = '1.20241028.01.00') {
    return [
        'client' => [
            'clientName'    => $clientName,
            'clientVersion' => $clientVersion,
            'hl'            => 'en',
            'gl'            => 'ID'
        ]
    ];
}

/**
 * Header HTTP standar untuk request JSON ke YouTube Music / YouTube Web
 */
function apolloInnertubeHeaders($origin = 'https://music.youtube.com') {
    return [
        'Content-Type: application/json',
        'Origin: ' . $origin,
        'Referer: ' . rtrim($origin, '/') . '/'
    ];
}

/**
 * Ubah teks durasi "M:SS" atau "H:MM:SS" menjadi [detik, "MM:SS"]
 */
function apolloParseDurationText($durText, $fallbackFormatted = '03:30') {
    $durText = trim((string)$durText);
    if ($durText === '' || strpos($durText, ':') === false) {
        return [0, $fallbackFormatted];
    }

    $parts = array_map('intval', explode(':', $durText));
    if (count($parts) === 3) {
        $sec = $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
        return [$sec, sprintf('%02d:%02d:%02d', $parts[0], $parts[1], $parts[2])];
    }
    if (count($parts) === 2) {
        $sec = $parts[0] * 60 + $parts[1];
        return [$sec, sprintf('%02d:%02d', $parts[0], $parts[1])];
    }

    return [0, $fallbackFormatted];
}

/**
 * Tingkatkan resolusi URL cover album YouTube Music menjadi 544x544 tajam
 */
function apolloUpgradeYtmThumb($url) {
    if (empty($url)) return '';
    if (strpos($url, 'googleusercontent.com') !== false || strpos($url, 'ggpht.com') !== false) {
        return preg_replace('/=w\d+-h\d+[^&?]*/', '=w544-h544-l90-rj', $url);
    }
    return $url;
}

/**
 * Parse satu node musicResponsiveListItemRenderer dari YouTube Music menjadi array lagu APOLLO
 */
function apolloParseYtmResponsiveTrack($r, $filterQuery = '', $opts = []) {
    $requireAlbumArt = $opts['require_album_art'] ?? false;
    $strictAlbumFilter = $opts['strict_album_filter'] ?? false;
    $defaultArtist = $opts['default_artist'] ?? 'Unknown Artist';
    $defaultAlbum = $opts['default_album'] ?? 'YouTube Music Single';

    $vId = $r['playlistItemData']['videoId']
        ?? ($r['navigationEndpoint']['watchEndpoint']['videoId'] ?? '');
    if (empty($vId)) return null;

    // 1. Ambil judul lagu dari kolom pertama
    $titleRuns = $r['flexColumns'][0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? [];
    $rawTitle = '';
    foreach ($titleRuns as $tr) {
        $rawTitle .= ($tr['text'] ?? '');
    }
    $rawTitle = trim($rawTitle);
    if ($rawTitle === '') return null;

    // 2. Pisahkan segmen Artis • Album • Durasi dari kolom kedua
    $metaRuns = $r['flexColumns'][1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? [];
    $segments = [];
    $currentSeg = '';
    foreach ($metaRuns as $mr) {
        $txt = $mr['text'] ?? '';
        if (trim($txt) === '•' || $txt === ' • ') {
            if (trim($currentSeg) !== '') {
                $segments[] = trim($currentSeg);
            }
            $currentSeg = '';
        } else {
            $currentSeg .= $txt;
        }
    }
    if (trim($currentSeg) !== '') {
        $segments[] = trim($currentSeg);
    }

    // Hapus label tipe di awal seperti "Song" / "Single"
    if (!empty($segments) && in_array(strtolower($segments[0]), ['song', 'single', 'track', 'video'], true)) {
        array_shift($segments);
    }

    $durationText = '';
    $filteredSegments = [];
    foreach ($segments as $seg) {
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $seg)) {
            $durationText = $seg;
        } elseif (preg_match('/\b(plays|views)\b/i', $seg)) {
            continue;
        } else {
            $filteredSegments[] = $seg;
        }
    }

    $artistName = $filteredSegments[0] ?? $defaultArtist;
    $albumName = $filteredSegments[1] ?? $defaultAlbum;
    list($durationSec, $formattedDur) = apolloParseDurationText($durationText);

    // 3. Validasi apakah item benar-benar lagu tunggal (bukan kompilasi / non-musik)
    $titleToValidate = $strictAlbumFilter ? ($rawTitle . ' ' . $albumName) : $rawTitle;
    if (!isLegitimateMusicTrack($titleToValidate, $durationSec, $artistName . ' - Topic', $filterQuery)) {
        return null;
    }

    // 4. Ambil artwork resolusi tinggi
    $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
    $thumbUrl = '';
    if (!empty($thumbs) && is_array($thumbs)) {
        $lastThumb = end($thumbs);
        $thumbUrl = $lastThumb['url'] ?? '';
    }

    if ($requireAlbumArt && empty($thumbUrl)) {
        return null;
    }

    $hasAlbumArt = false;
    if (!empty($thumbUrl)) {
        if (strpos($thumbUrl, 'googleusercontent.com') !== false || strpos($thumbUrl, 'ggpht.com') !== false) {
            $thumbUrl = apolloUpgradeYtmThumb($thumbUrl);
            $hasAlbumArt = true;
        }
    } else {
        $thumbUrl = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
    }

    return [
        'video_id'    => $vId,
        'title'       => $rawTitle,
        'artist'      => $artistName,
        'album'       => $albumName,
        'duration'    => $formattedDur,
        'thumbnail'   => $thumbUrl,
        'source'      => ($requireAlbumArt || $hasAlbumArt) ? 'youtube_music' : 'youtube',
        'is_official' => ($requireAlbumArt || $hasAlbumArt)
    ];
}
