<?php
/**
 * APOLLO — Service Profil, Album Resmi, & Artis Terkait
 * Mengambil langsung dari halaman Channel Resmi Artis YouTube Music (browseId UC...)
 * sehingga Album, EP/Single, Biodata, Lagu, & Artis Terkait 100% milik artis tersebut
 * (tidak tercampur podcast atau album milik artis lain yang namanya mirip).
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

/**
 * Cek apakah nama artis pada lagu/album cocok dengan artis yang sedang dibuka
 */
function apolloArtistNameMatches($candidateArtist, $targetArtist) {
    $a = strtolower(trim(preg_replace('/\s+/', ' ', (string)$candidateArtist)));
    $b = strtolower(trim(preg_replace('/\s+/', ' ', (string)$targetArtist)));
    if ($a === '' || $b === '') return false;
    if ($a === $b) return true;

    // Cocok jika kolaborasi ("Perunggu & ...", "..., Perunggu", "feat. Perunggu")
    $parts = preg_split('/\s*(?:,|&|\bfeat\.?|\bft\.?|\bx\b|\bwith\b)\s*/i', $a);
    foreach ($parts as $p) {
        if (trim($p) === $b) return true;
    }
    return false;
}

/**
 * Ekstrak data resmi (Bio, Top Songs, Albums, Singles/EPs, & Related Artists) dari halaman Channel UC...
 */
function apolloParseOfficialArtistChannel($browseBody, $canonicalArtistName) {
    $data = json_decode($browseBody, true);
    $out = [
        'desc'    => '',
        'photo'   => '',
        'tracks'  => [],
        'albums'  => [],
        'artists' => []
    ];

    if (!is_array($data)) return $out;

    // 1. Header Artis Resmi (Deskripsi / Biografi & Foto HD)
    $hdr = $data['header']['musicImmersiveHeaderRenderer']
        ?? ($data['header']['musicVisualHeaderRenderer'] ?? null);
    if (is_array($hdr)) {
        $desc = '';
        foreach (($hdr['description']['runs'] ?? []) as $r) {
            $desc .= ($r['text'] ?? '');
        }
        $out['desc'] = trim($desc);

        $thumbs = $hdr['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
        if (!empty($thumbs) && is_array($thumbs)) {
            $lastT = end($thumbs);
            $out['photo'] = apolloUpgradeYtmThumb($lastT['url'] ?? '');
        }
    }

    // 2. Telusuri setiap rak (shelf) resmi pada halaman channel artis
    $sections = $data['contents']['singleColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
    $seenAlbumIds = [];
    $seenArtistIds = [];

    foreach ($sections as $sec) {
        // Rak Lagu Terpopuler (musicShelfRenderer)
        if (isset($sec['musicShelfRenderer'])) {
            foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
                if (empty($item['musicResponsiveListItemRenderer'])) continue;
                $parsed = apolloParseYtmResponsiveTrack($item['musicResponsiveListItemRenderer'], $canonicalArtistName, [
                    'require_album_art'   => false,
                    'strict_album_filter' => true,
                    'default_artist'      => $canonicalArtistName,
                    'default_album'       => 'Top Track'
                ]);
                if ($parsed) {
                    $parsed['artist'] = $canonicalArtistName;
                    $out['tracks'][] = $parsed;
                }
            }
        }

        // Rak Carousel: "Albums", "Singles & EPs", dan "Fans might also like"
        if (!isset($sec['musicCarouselShelfRenderer'])) continue;
        $carousel = $sec['musicCarouselShelfRenderer'];

        $shelfTitle = '';
        foreach (($carousel['header']['musicCarouselShelfBasicHeaderRenderer']['title']['runs'] ?? []) as $r) {
            $shelfTitle .= ($r['text'] ?? '');
        }
        $lowShelf = strtolower(trim($shelfTitle));

        $isAlbumShelf  = (strpos($lowShelf, 'album') !== false || strpos($lowShelf, 'single') !== false);
        $isArtistShelf = (strpos($lowShelf, 'fans might also like') !== false || strpos($lowShelf, 'similar') !== false || strpos($lowShelf, 'related') !== false);

        foreach (($carousel['contents'] ?? []) as $item) {
            $m = $item['musicTwoRowItemRenderer'] ?? null;
            if (!$m) continue;

            $itemBrowseId = $m['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
            if ($itemBrowseId === '') continue;

            $title = '';
            foreach (($m['title']['runs'] ?? []) as $r) $title .= ($r['text'] ?? '');
            $title = trim($title);
            if ($title === '') continue;

            $subtitle = '';
            foreach (($m['subtitle']['runs'] ?? []) as $sr) $subtitle .= ($sr['text'] ?? '');
            $subtitle = trim($subtitle);

            $thumbs = $m['thumbnailRenderer']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
            $lastT = (!empty($thumbs) && is_array($thumbs)) ? end($thumbs) : null;
            $artwork = apolloUpgradeYtmThumb($lastT['url'] ?? '');

            // Kumpulkan Album Resmi & EP / Single milik artis ini sendiri
            if ($isAlbumShelf && (strpos($itemBrowseId, 'MPREb_') === 0 || strpos($itemBrowseId, 'OLAK5uy_') === 0)) {
                if (isset($seenAlbumIds[$itemBrowseId])) continue;
                $seenAlbumIds[$itemBrowseId] = true;

                $releaseType = (strpos($lowShelf, 'album') !== false) ? 'ALBUM' : 'SINGLE / EP';
                $year = '';
                foreach (explode('•', $subtitle) as $part) {
                    $p = trim($part);
                    if (preg_match('/^(19|20)\d{2}$/', $p)) {
                        $year = $p;
                    } elseif (in_array(strtoupper($p), ['ALBUM', 'EP', 'SINGLE'], true)) {
                        $releaseType = strtoupper($p);
                    }
                }

                $out['albums'][] = [
                    'browse_id'    => $itemBrowseId,
                    'title'        => $title,
                    'artist'       => $canonicalArtistName,
                    'year'         => $year,
                    'release_type' => $releaseType,
                    'artwork'      => $artwork
                ];
            }

            // Kumpulkan Artis Terkait ("Fans might also like")
            if ($isArtistShelf && strpos($itemBrowseId, 'UC') === 0) {
                if (isset($seenArtistIds[$itemBrowseId])) continue;
                $seenArtistIds[$itemBrowseId] = true;

                $out['artists'][] = [
                    'browse_id' => $itemBrowseId,
                    'name'      => $title,
                    'subtitle'  => $subtitle ?: 'Related Artist',
                    'photo'     => $artwork
                ];
            }
        }
    }

    return $out;
}

function apolloBuildArtistDossier($name, $requestedBrowseId = '') {
    $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    $curated = apolloGetCuratedArtistCatalog();

    // Cache singkat 15 menit per artis agar buka profil artis cepat & ringan di Vercel
    $cacheKey = md5(strtolower(trim($name)) . '|' . trim($requestedBrowseId));
    $cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/apollo_artist_dossier_v2_' . $cacheKey . '.json';
    if (is_file($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if (!empty($raw)) {
            $cached = json_decode($raw, true);
            if (is_array($cached) && !empty($cached['dossier']) && (time() - (int)($cached['created_at'] ?? 0)) < 900) {
                return $cached['dossier'];
            }
        }
    }

    $meta = $curated[$key] ?? [
        'name'       => strtoupper($name),
        'location'   => 'YOUTUBE MUSIC · OFFICIAL ARTIST',
        'desc'       => '',
        'photo'      => '',
        'browse_id'  => '',
        'catalog_id' => 'APOLLO-REF-' . strtoupper(substr(md5($name), 0, 8))
    ];

    // 1. Cari profil artis resmi + lagu-lagunya secara paralel
    $step1Requests = [
        'songs' => [
            'url'     => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
            'method'  => 'POST',
            'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
            'body'    => json_encode([
                'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                'query'   => $name,
                'params'  => 'EgWKAQIIAWoKEAkQBRAKEAMQBA=='
            ]),
            'timeout' => 4
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
            'timeout' => 4
        ]
    ];

    if ($requestedBrowseId !== '' && strpos($requestedBrowseId, 'UC') === 0) {
        $step1Requests['channel'] = [
            'url'     => 'https://music.youtube.com/youtubei/v1/browse?prettyPrint=false',
            'method'  => 'POST',
            'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
            'body'    => json_encode([
                'context'  => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                'browseId' => $requestedBrowseId
            ]),
            'timeout' => 4
        ];
    }

    $step1 = apolloHttpParallel($step1Requests, 4);

    // Identifikasi artis utama & channel UC resminya
    $canonicalName = $name;
    $matchedArtists = [];
    if (($step1['artists']['code'] ?? 0) === 200 && !empty($step1['artists']['body'])) {
        $matchedArtists = apolloParseYtmArtistsFromBody($step1['artists']['body'], 8);
    }

    $primaryArtist = null;
    foreach ($matchedArtists as $cand) {
        if (strtolower(trim($cand['name'])) === strtolower(trim($name))) {
            $primaryArtist = $cand;
            break;
        }
    }
    if (!$primaryArtist && !empty($matchedArtists[0])) {
        $primaryArtist = $matchedArtists[0];
    }

    $channelBrowseId = $requestedBrowseId;
    if ($primaryArtist) {
        $canonicalName = $primaryArtist['name'];
        $meta['name'] = strtoupper($canonicalName);
        if (!empty($primaryArtist['photo'])) {
            $meta['photo'] = $primaryArtist['photo'];
        }
        if (!empty($primaryArtist['browse_id'])) {
            $meta['browse_id'] = $primaryArtist['browse_id'];
            if ($channelBrowseId === '') {
                $channelBrowseId = $primaryArtist['browse_id'];
            }
        }
        if (!empty($primaryArtist['subtitle']) && !isset($curated[$key])) {
            $meta['location'] = strtoupper($primaryArtist['subtitle']);
        }
    }

    // 2. Ambil halaman Channel Resmi Artis (UC...) untuk mendapatkan Album & EP/Single asli milik artis ini
    $channelData = ['desc' => '', 'photo' => '', 'tracks' => [], 'albums' => [], 'artists' => []];
    if (isset($step1['channel']) && ($step1['channel']['code'] ?? 0) === 200 && !empty($step1['channel']['body'])) {
        $channelData = apolloParseOfficialArtistChannel($step1['channel']['body'], $canonicalName);
    } elseif ($channelBrowseId !== '' && strpos($channelBrowseId, 'UC') === 0) {
        $chResp = apolloHttpRequest(
            'https://music.youtube.com/youtubei/v1/browse?prettyPrint=false',
            'POST',
            apolloInnertubeHeaders('https://music.youtube.com'),
            json_encode([
                'context'  => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                'browseId' => $channelBrowseId
            ]),
            4
        );
        if ($chResp['code'] === 200 && !empty($chResp['body'])) {
            $channelData = apolloParseOfficialArtistChannel($chResp['body'], $canonicalName);
        }
    }

    if ($meta['desc'] === '') {
        $meta['desc'] = $channelData['desc'] !== ''
            ? $channelData['desc']
            : 'Official discography, studio albums, EPs, and singles by ' . htmlspecialchars($canonicalName) . '.';
    }
    if ($meta['photo'] === '' && $channelData['photo'] !== '') {
        $meta['photo'] = $channelData['photo'];
    }

    // 3. Gabungkan Lagu: Top Songs dari Channel Resmi + Hasil Search yang artisnya cocok
    $artistTracks = [];
    $seenIds = [];
    $firstThumb = '';

    foreach ($channelData['tracks'] as $ct) {
        if (isset($seenIds[$ct['video_id']])) continue;
        $seenIds[$ct['video_id']] = true;
        if ($firstThumb === '' && !empty($ct['thumbnail'])) $firstThumb = $ct['thumbnail'];
        $artistTracks[] = $ct;
    }

    if (($step1['songs']['code'] ?? 0) === 200 && !empty($step1['songs']['body'])) {
        $ytmData = json_decode($step1['songs']['body'], true);
        $sections = $ytmData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];

        foreach ($sections as $sec) {
            foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
                if (empty($item['musicResponsiveListItemRenderer'])) continue;

                $parsed = apolloParseYtmResponsiveTrack($item['musicResponsiveListItemRenderer'], $canonicalName, [
                    'require_album_art'   => false,
                    'strict_album_filter' => true,
                    'default_artist'      => $canonicalName,
                    'default_album'       => 'Official Release'
                ]);

                if (!$parsed || isset($seenIds[$parsed['video_id']])) continue;
                // Hanya masukkan lagu yang benar-benar dibawakan oleh artis ini
                if (!apolloArtistNameMatches($parsed['artist'], $canonicalName) && !apolloArtistNameMatches($parsed['artist'], $name)) {
                    continue;
                }

                $seenIds[$parsed['video_id']] = true;
                if ($firstThumb === '' && !empty($parsed['thumbnail'])) $firstThumb = $parsed['thumbnail'];
                $artistTracks[] = $parsed;

                if (count($artistTracks) >= 16) break 2;
            }
        }
    }

    // 4. Album Resmi: Prioritaskan langsung dari Channel Resmi Artis (100% milik artis tersebut!)
    $artistAlbums = array_slice($channelData['albums'], 0, 12);

    // Fallback jika channel belum memiliki rak album: cari di Albums dengan filter ketat nama artis harus cocok!
    if (empty($artistAlbums)) {
        $searchedAlbums = apolloSearchAlbums($canonicalName, 16);
        foreach ($searchedAlbums as $alb) {
            if (apolloArtistNameMatches($alb['artist'], $canonicalName) || apolloArtistNameMatches($alb['artist'], $name)) {
                $artistAlbums[] = $alb;
                if (count($artistAlbums) >= 8) break;
            }
        }
    }

    // 5. Artis Terkait: Gabungkan "Fans might also like" + artis bernama mirip
    $relatedArtists = [];
    $seenRel = [strtolower(trim($canonicalName)) => true];

    foreach (array_merge($channelData['artists'], $matchedArtists) as $rel) {
        $rKey = strtolower(trim($rel['name'] ?? ''));
        if ($rKey === '' || isset($seenRel[$rKey])) continue;
        $seenRel[$rKey] = true;
        $relatedArtists[] = $rel;
        if (count($relatedArtists) >= 8) break;
    }

    if ($meta['photo'] === '') {
        $meta['photo'] = $firstThumb !== '' ? $firstThumb : 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg';
    }

    $meta['tracks']  = $artistTracks;
    $meta['albums']  = $artistAlbums;
    $meta['artists'] = $relatedArtists;

    if (!empty($artistTracks) || !empty($artistAlbums)) {
        @file_put_contents($cacheFile, json_encode([
            'created_at' => time(),
            'dossier'    => $meta
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    return $meta;
}
