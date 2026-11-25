<?php
/**
 * APOLLO — Service Profil & Diskografi Artis
 * Menyusun biodata artis serta daftar lagu resminya dari YouTube Music / yt-dlp lokal.
 */

function apolloGetCuratedArtistCatalog() {
    return [
        'queen' => [
            'name'       => 'QUEEN',
            'location'   => 'LONDON, UK · TRIDENT STUDIOS',
            'desc'       => 'Iconic British rock band formed in London in 1970 by Freddie Mercury, Brian May, Roger Taylor, and John Deacon. Renowned for their operatic anthems, progressive rock orchestration, and multi-layered vocal harmonies.',
            'photo'      => 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg',
            'catalog_id' => 'QN-ARCH-1973-1991'
        ],
        'theweeknd' => [
            'name'       => 'THE WEEKND',
            'location'   => 'TORONTO, CA · XO RECORDS',
            'desc'       => 'Canadian singer, songwriter, and record producer Abel Tesfaye. Celebrated for pioneering dark alternative R&B and retro 1980s synth-pop with international chart-topping master singles.',
            'photo'      => 'https://i.ytimg.com/vi/4NRXx6U8ABQ/hqdefault.jpg',
            'catalog_id' => 'XO-ARCH-2011-2024'
        ],
        'daftpunk' => [
            'name'       => 'DAFT PUNK',
            'location'   => 'PARIS, FR · DAFT HOUSE',
            'desc'       => 'French electronic music duo formed in 1993 by Guy-Manuel de Homem-Christo and Thomas Bangalter. Widely considered one of the most influential acts in dance music history, pioneering the French touch movement.',
            'photo'      => 'https://i.ytimg.com/vi/5NV6Rdv1a3I/hqdefault.jpg',
            'catalog_id' => 'DP-ARCH-1997-2013'
        ],
        'radiohead' => [
            'name'       => 'RADIOHEAD',
            'location'   => 'OXFORDSHIRE, UK · COURTYARD STUDIO',
            'desc'       => 'English rock band formed in Abingdon in 1985. Noted for their continuous experimental evolution from anthemic alternative rock to electronic glitch, jazz orchestration, and ambient textures.',
            'photo'      => 'https://i.ytimg.com/vi/XFkzRNyygfk/hqdefault.jpg',
            'catalog_id' => 'RH-ARCH-1992-2016'
        ]
    ];
}

function apolloBuildArtistDossier($name) {
    $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    $curated = apolloGetCuratedArtistCatalog();

    $meta = $curated[$key] ?? [
        'name'       => strtoupper($name),
        'location'   => 'GLOBAL ARTIST CATALOG',
        'desc'       => 'Industrial audio archive and discography master recordings for ' . htmlspecialchars($name) . '. Pure analog and high-fidelity signal master tape recordings.',
        'photo'      => '',
        'catalog_id' => 'APOLLO-REF-' . strtoupper(substr(md5($name), 0, 8))
    ];

    $artistTracks = [];
    $firstThumb = '';
    $seenIds = [];

    // 1. Ambil lagu resmi artis dari YouTube Music (~0.8 detik, jalan di lokal maupun Vercel)
    $ytmResp = apolloHttpRequest(
        'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
        'POST',
        apolloInnertubeHeaders('https://music.youtube.com'),
        json_encode([
            'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
            'query'   => $name,
            'params'  => 'EgWKAQIIAWoKEAkQBRAKEAMQBA=='
        ]),
        4
    );

    if ($ytmResp['code'] === 200 && !empty($ytmResp['body'])) {
        $ytmData = json_decode($ytmResp['body'], true);
        $sections = $ytmData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];

        foreach ($sections as $sec) {
            foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
                if (empty($item['musicResponsiveListItemRenderer'])) continue;

                $parsed = apolloParseYtmResponsiveTrack($item['musicResponsiveListItemRenderer'], $name, [
                    'require_album_art'   => false,
                    'strict_album_filter' => true,
                    'default_artist'      => $meta['name'],
                    'default_album'       => 'Essential Master Tape'
                ]);

                if (!$parsed || isset($seenIds[$parsed['video_id']])) continue;
                $seenIds[$parsed['video_id']] = true;

                if (empty($firstThumb)) {
                    $firstThumb = $parsed['thumbnail'];
                }
                $artistTracks[] = $parsed;

                if (count($artistTracks) >= 20) break 2;
            }
        }
    }

    // 2. Fallback lokal menggunakan bin/yt-dlp jika diperlukan
    if (!apolloIsVercelRuntime() && empty($artistTracks) && (function_exists('exec') || function_exists('shell_exec'))) {
        $ytDlpBin = getYtDlpBinary();
        $cmd = escapeshellcmd($ytDlpBin) . ' --dump-json --flat-playlist --no-warnings ' . escapeshellarg("ytsearch25:{$name}") . ' 2>/dev/null';

        $lines = [];
        @exec($cmd, $lines);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] !== '{') continue;

            $entry = json_decode($line, true);
            if (!is_array($entry) || empty($entry['id'])) continue;

            $vId = $entry['id'];
            if (isset($seenIds[$vId])) continue;

            $rawTitle = $entry['title'] ?? 'Master Audio Recording';
            $uploader = $entry['uploader'] ?? $entry['channel'] ?? '';
            $durationSec = (int)($entry['duration'] ?? 0);

            if (!isLegitimateMusicTrack($rawTitle, $durationSec, $uploader, $name)) continue;

            list($cleanTitle, $cleanArtist) = cleanMusicDetails($rawTitle, $uploader);
            $thumb = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
            if (!empty($entry['thumbnails']) && is_array($entry['thumbnails'])) {
                $lastThumb = end($entry['thumbnails']);
                if (!empty($lastThumb['url'])) $thumb = $lastThumb['url'];
            }
            if (empty($firstThumb)) $firstThumb = $thumb;

            $seenIds[$vId] = true;
            $artistTracks[] = [
                'video_id'  => $vId,
                'title'     => $cleanTitle,
                'artist'    => !empty($cleanArtist) ? $cleanArtist : $meta['name'],
                'album'     => 'Essential Master Tape',
                'duration'  => formatSeconds($durationSec),
                'thumbnail' => $thumb
            ];

            if (count($artistTracks) >= 20) break;
        }
    }

    if (empty($meta['photo'])) {
        $meta['photo'] = !empty($firstThumb) ? $firstThumb : 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg';
    }
    $meta['tracks'] = $artistTracks;

    return $meta;
}
