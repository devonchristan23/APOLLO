<?php
/**
 * APOLLO — Service Rekomendasi Lagu YouTube Music (Halaman Home)
 * Mengambil lagu individual populer secara paralel dan menyimpannya di cache sementara (/tmp).
 */

function apolloGetHomeRecommendations() {
    $cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/apollo_ytm_home_songs_v3.json';
    $pool = [];

    // 1. Cek cache 10 menit agar loading halaman Home selalu instan
    if (is_file($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if (!empty($raw)) {
            $cached = json_decode($raw, true);
            if (is_array($cached) && !empty($cached['tracks']) && (time() - (int)($cached['created_at'] ?? 0)) < 600) {
                $pool = $cached['tracks'];
            }
        }
    }

    // 2. Jika cache kosong/kedaluwarsa, ambil lagu dari artis pilihan secara paralel
    if (empty($pool)) {
        $seen = [];
        $artistSeeds = [
            'Barasuara', 'Hindia', 'Perunggu', 'Tulus', 'Sheila On 7',
            'Bernadya', 'Juicy Luicy', 'Nadin Amizah', 'Payung Teduh', 'Dewa 19',
            'Arctic Monkeys', 'The Weeknd', 'Coldplay', 'Radiohead', 'Bruno Mars',
            'Daniel Caesar', 'Daft Punk', 'Oasis', 'Billie Eilish', 'Queen'
        ];
        shuffle($artistSeeds);

        $seedRequests = [];
        foreach (array_slice($artistSeeds, 0, 2) as $idx => $seedArtist) {
            $seedRequests['seed_' . $idx] = [
                'url'     => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
                'method'  => 'POST',
                'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
                'body'    => json_encode([
                    'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                    'query'   => $seedArtist,
                    'params'  => 'EgWKAQIIAWoKEAkQBRAKEAMQBA=='
                ]),
                'timeout' => 4
            ];
        }

        $seedResponses = apolloHttpParallel($seedRequests, 4);
        foreach ($seedResponses as $seedResp) {
            if ($seedResp['code'] !== 200 || empty($seedResp['body'])) continue;

            $seedData = json_decode($seedResp['body'], true);
            $sections = $seedData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
            $perArtistCount = 0;

            foreach ($sections as $sec) {
                foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
                    if (empty($item['musicResponsiveListItemRenderer'])) continue;

                    $parsed = apolloParseYtmResponsiveTrack($item['musicResponsiveListItemRenderer'], '', [
                        'require_album_art'   => true,
                        'strict_album_filter' => true,
                        'default_artist'      => 'YouTube Music Artist'
                    ]);

                    if ($parsed && !isset($seen[$parsed['video_id']])) {
                        $seen[$parsed['video_id']] = true;
                        $pool[] = $parsed;
                        $perArtistCount++;
                        if ($perArtistCount >= 8) break 2;
                    }
                }
            }
        }

        if (!empty($pool)) {
            @file_put_contents($cacheFile, json_encode([
                'created_at' => time(),
                'tracks'     => $pool
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    shuffle($pool);

    // 3. Susun 4 kartu Recommended dari artis yang berbeda
    $recommendedCards = [];
    $usedArtists = [];
    foreach ($pool as $track) {
        $artKey = strtolower(trim($track['artist']));
        if (isset($usedArtists[$artKey])) continue;
        $usedArtists[$artKey] = true;

        $recommendedCards[] = [
            'title'     => $track['title'],
            'artist'    => $track['artist'],
            'badge'     => $track['duration'] ?: 'SONG',
            'artwork'   => $track['thumbnail'],
            'leadTrack' => $track
        ];

        if (count($recommendedCards) >= 4) break;
    }

    return [
        'albums' => $recommendedCards,
        'tracks' => array_slice($pool, 0, 14)
    ];
}
