<?php
/**
 * APOLLO — Service Profil, Album Resmi, & Artis Terkait
 * Menembak 3 endpoint YouTube Music secara paralel (~0.9 detik) agar saat user klik artis
 * di bawah lagu mana pun, langsung muncul lagu, album resmi, dan daftar artisnya.
 */

require_once __DIR__ . '/search_service.php';

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
        'location'   => 'YOUTUBE MUSIC · ARTIST CATALOG',
        'desc'       => 'Official songs, studio albums, and matching artist profiles for ' . htmlspecialchars($name) . '.',
        'photo'      => '',
        'catalog_id' => 'APOLLO-REF-' . strtoupper(substr(md5($name), 0, 8))
    ];

    $artistTracks = [];
    $artistAlbums = [];
    $relatedArtists = [];
    $firstThumb = '';
    $seenIds = [];

    // 1. Ambil Lagu + Album Resmi + Daftar Artis secara paralel (~0.9 detik)
    $parallel = apolloHttpParallel([
        'songs' => [
            'url'     => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
            'method'  => 'POST',
            'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
            'body'    => json_encode([
                'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                'query'   => $name,
                'params'  => 'EgWKAQIIAWoKEAkQBRAKEAMQBA=='
            ]),
            'timeout' => 5
        ],
        'albums' => [
            'url'     => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
            'method'  => 'POST',
            'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
            'body'    => json_encode([
                'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                'query'   => $name,
                'params'  => 'EgWKAQIYAWoKEAkQBRAKEAMQBA=='
            ]),
            'timeout' => 5
        ],
        'artists' => [
            'url'     => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
            'method'  => 'POST',
            'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
            'body'    => json_encode([
                'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                'query'   => $name,
                'params'  => 'EgWKAQIgAWoKEAkQBRAKEAMQBA=='
            ]),
            'timeout' => 5
        ]
    ], 5);

    // Parse Lagu
    if (($parallel['songs']['code'] ?? 0) === 200 && !empty($parallel['songs']['body'])) {
        $ytmData = json_decode($parallel['songs']['body'], true);
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

                if (count($artistTracks) >= 16) break 2;
            }
        }
    }

    // Parse Album Resmi
    if (($parallel['albums']['code'] ?? 0) === 200 && !empty($parallel['albums']['body'])) {
        $artistAlbums = apolloParseYtmAlbumsFromBody($parallel['albums']['body'], 8);
    }

    // Parse Daftar Artis & Gunakan Foto Profil Artis Resmi #1
    if (($parallel['artists']['code'] ?? 0) === 200 && !empty($parallel['artists']['body'])) {
        $relatedArtists = apolloParseYtmArtistsFromBody($parallel['artists']['body'], 8);
        if (!empty($relatedArtists[0]['photo'])) {
            $meta['photo'] = $relatedArtists[0]['photo'];
        }
        if (!empty($relatedArtists[0]['browse_id'])) {
            $meta['browse_id'] = $relatedArtists[0]['browse_id'];
        }
        if (!empty($relatedArtists[0]['subtitle']) && !isset($curated[$key])) {
            $meta['location'] = strtoupper($relatedArtists[0]['subtitle']);
        }
    }

    if (empty($meta['photo'])) {
        $meta['photo'] = !empty($firstThumb) ? $firstThumb : 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg';
    }

    $meta['tracks']  = $artistTracks;
    $meta['albums']  = $artistAlbums;
    $meta['artists'] = $relatedArtists;

    return $meta;
}
