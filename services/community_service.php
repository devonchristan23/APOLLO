<?php
/**
 * APOLLO — Service Community Playlists & Albums (YouTube Music)
 * Mengambil daftar playlist buatan komunitas beserta isi lagunya.
 */

/**
 * Ambil 4 playlist komunitas populer dari YouTube Music (dengan cache 15 menit)
 */
function apolloGetCommunityPlaylists() {
    $cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/apollo_ytm_community_list_v1.json';
    $pool = [];

    if (is_file($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if (!empty($raw)) {
            $cached = json_decode($raw, true);
            if (is_array($cached) && !empty($cached['items']) && (time() - (int)($cached['created_at'] ?? 0)) < 900) {
                $pool = $cached['items'];
            }
        }
    }

    if (empty($pool)) {
        $seen = [];
        $commSeeds = ['Indie Indonesia', 'Chill Vibes', 'Pop Hits Playlist'];
        shuffle($commSeeds);

        $commParallel = apolloHttpParallel([
            'home' => [
                'url'     => 'https://music.youtube.com/youtubei/v1/browse?prettyPrint=false',
                'method'  => 'POST',
                'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
                'body'    => json_encode([
                    'context'  => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                    'browseId' => 'FEmusic_home'
                ]),
                'timeout' => 4
            ],
            'seed' => [
                'url'     => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
                'method'  => 'POST',
                'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
                'body'    => json_encode([
                    'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                    'query'   => $commSeeds[0],
                    'params'  => 'EgWKAQIoAWoKEAkQBRAKEAMQBA=='
                ]),
                'timeout' => 4
            ]
        ], 4);

        // 1. Parse carousel dari YouTube Music Home
        if (($commParallel['home']['code'] ?? 0) === 200 && !empty($commParallel['home']['body'])) {
            $homeData = json_decode($commParallel['home']['body'], true);
            $sections = $homeData['contents']['singleColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];

            foreach ($sections as $sec) {
                foreach (($sec['musicCarouselShelfRenderer']['contents'] ?? []) as $item) {
                    $m = $item['musicTwoRowItemRenderer'] ?? null;
                    if (!$m) continue;

                    $browseId = $m['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
                    if (empty($browseId) || strpos($browseId, 'VL') !== 0 || isset($seen[$browseId])) continue;

                    $title = '';
                    foreach (($m['title']['runs'] ?? []) as $r) $title .= ($r['text'] ?? '');
                    $title = trim($title);
                    if ($title === '') continue;

                    $subtitle = '';
                    foreach (($m['subtitle']['runs'] ?? []) as $sr) $subtitle .= ($sr['text'] ?? '');
                    $subtitle = trim($subtitle);

                    $thumbs = $m['thumbnailRenderer']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
                    $lastT = (!empty($thumbs) && is_array($thumbs)) ? end($thumbs) : null;
                    $thumbUrl = apolloUpgradeYtmThumb($lastT['url'] ?? '');
                    if ($thumbUrl === '') continue;

                    $seen[$browseId] = true;
                    $pool[] = [
                        'browse_id' => $browseId,
                        'title'     => $title,
                        'creator'   => $subtitle ?: 'YouTube Music Community',
                        'artwork'   => $thumbUrl
                    ];
                }
            }
        }

        // 2. Parse hasil filter Community Playlists
        if (($commParallel['seed']['code'] ?? 0) === 200 && !empty($commParallel['seed']['body'])) {
            $seedData = json_decode($commParallel['seed']['body'], true);
            $sSecs = $seedData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];

            foreach ($sSecs as $sec) {
                foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
                    $r = $item['musicResponsiveListItemRenderer'] ?? null;
                    if (!$r) continue;

                    $browseId = $r['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
                    if (empty($browseId) || strpos($browseId, 'VL') !== 0 || isset($seen[$browseId])) continue;

                    $title = '';
                    foreach (($r['flexColumns'][0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $tr) {
                        $title .= ($tr['text'] ?? '');
                    }
                    $title = trim($title);
                    if ($title === '') continue;

                    $subtitle = '';
                    foreach (($r['flexColumns'][1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $mr) {
                        $subtitle .= ($mr['text'] ?? '');
                    }
                    $subtitle = trim(preg_replace('/^Playlist\s*•\s*/i', '', $subtitle));

                    $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
                    $lastT = (!empty($thumbs) && is_array($thumbs)) ? end($thumbs) : null;
                    $thumbUrl = apolloUpgradeYtmThumb($lastT['url'] ?? '');
                    if ($thumbUrl === '') continue;

                    $seen[$browseId] = true;
                    $pool[] = [
                        'browse_id' => $browseId,
                        'title'     => $title,
                        'creator'   => $subtitle ?: 'YouTube Music Community',
                        'artwork'   => $thumbUrl
                    ];
                }
            }
        }

        if (!empty($pool)) {
            @file_put_contents($cacheFile, json_encode([
                'created_at' => time(),
                'items'      => $pool
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    shuffle($pool);
    return array_slice($pool, 0, 4);
}

/**
 * Ambil daftar lagu di dalam sebuah Community Playlist berdasarkan browse_id
 */
function apolloGetCommunityPlaylistTracks($browseId) {
    $plCacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/apollo_ytm_comm_tracks_' . md5($browseId) . '.json';

    if (is_file($plCacheFile)) {
        $raw = @file_get_contents($plCacheFile);
        if (!empty($raw)) {
            $cached = json_decode($raw, true);
            if (is_array($cached) && !empty($cached['tracks']) && (time() - (int)($cached['created_at'] ?? 0)) < 1800) {
                return $cached['tracks'];
            }
        }
    }

    $resp = apolloHttpRequest(
        'https://music.youtube.com/youtubei/v1/browse?prettyPrint=false',
        'POST',
        apolloInnertubeHeaders('https://music.youtube.com'),
        json_encode([
            'context'  => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
            'browseId' => $browseId
        ]),
        7
    );

    $tracks = [];
    if ($resp['code'] === 200 && !empty($resp['body'])) {
        $data = json_decode($resp['body'], true);
        $rawItems = [];
        $walk = function ($node) use (&$walk, &$rawItems) {
            if (!is_array($node)) return;
            if (isset($node['musicResponsiveListItemRenderer'])) {
                $rawItems[] = $node['musicResponsiveListItemRenderer'];
                return;
            }
            foreach ($node as $child) {
                if (is_array($child)) $walk($child);
            }
        };
        $walk($data);

        $seenVids = [];
        foreach ($rawItems as $r) {
            $vId = $r['playlistItemData']['videoId'] ?? ($r['navigationEndpoint']['watchEndpoint']['videoId'] ?? '');
            if (empty($vId) || isset($seenVids[$vId])) continue;

            $title = '';
            foreach (($r['flexColumns'][0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $tr) {
                $title .= ($tr['text'] ?? '');
            }
            $title = trim($title);
            if ($title === '') continue;

            $artist = '';
            foreach (($r['flexColumns'][1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $mr) {
                $txt = $mr['text'] ?? '';
                if (trim($txt) === '•') break;
                $artist .= $txt;
            }
            $artist = trim($artist) ?: 'Unknown Artist';

            $album = '';
            foreach (($r['flexColumns'][2]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $ar) {
                $album .= ($ar['text'] ?? '');
            }
            $album = trim($album);

            $durRaw = $r['fixedColumns'][0]['musicResponsiveListItemFixedColumnRenderer']['text']['runs'][0]['text'] ?? '';
            list($durSec, $formattedDur) = apolloParseDurationText($durRaw, '03:45');
            if ($durSec > 720) continue; // Lewati video kompilasi panjang

            $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
            $lastT = (!empty($thumbs) && is_array($thumbs)) ? end($thumbs) : null;
            $thumbUrl = apolloUpgradeYtmThumb($lastT['url'] ?? '') ?: "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";

            $seenVids[$vId] = true;
            $tracks[] = [
                'video_id'    => $vId,
                'title'       => $title,
                'artist'      => $artist,
                'album'       => $album,
                'duration'    => $formattedDur,
                'thumbnail'   => $thumbUrl,
                'source'      => 'youtube_music',
                'is_official' => true
            ];

            if (count($tracks) >= 50) break;
        }
    }

    if (!empty($tracks)) {
        @file_put_contents($plCacheFile, json_encode([
            'created_at' => time(),
            'tracks'     => $tracks
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    return $tracks;
}
