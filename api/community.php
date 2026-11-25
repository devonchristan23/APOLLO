<?php
/**
 * Apollo Minimalist Music Player - YouTube Music Community Playlists API (100% PHP Native)
 * Actions:
 *   - list: Fetch Trending & Popular Community-Created Playlists/Albums on YouTube Music
 *   - tracks: Fetch full playable song list inside a YouTube Music Community Playlist (browse_id)
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'list';

/**
 * Normalize "M:SS" / "MM:SS" duration text
 */
function normalizeCommDuration($durText) {
    $durText = trim((string)$durText);
    if ($durText === '' || strpos($durText, ':') === false) {
        return [0, '03:45'];
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
    return [0, '03:45'];
}

/**
 * Upgrade YouTube Music thumbnail URL to crisp 544x544 artwork
 */
function upgradeCommThumb($url) {
    if (empty($url)) return '';
    if (strpos($url, 'googleusercontent.com') !== false || strpos($url, 'ggpht.com') !== false) {
        return preg_replace('/=w\d+-h\d+[^&?]*/', '=w544-h544-l90-rj', $url);
    }
    return $url;
}

if ($action === 'list') {
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

        $homePayload = json_encode([
            'context' => [
                'client' => [
                    'clientName' => 'WEB_REMIX',
                    'clientVersion' => '1.20241028.01.00',
                    'hl' => 'en',
                    'gl' => 'ID'
                ]
            ],
            'browseId' => 'FEmusic_home'
        ]);

        $seedPayload = json_encode([
            'context' => [
                'client' => [
                    'clientName' => 'WEB_REMIX',
                    'clientVersion' => '1.20241028.01.00',
                    'hl' => 'en',
                    'gl' => 'ID'
                ]
            ],
            'query' => $commSeeds[0],
            // YouTube Music "Community playlists" filter param
            'params' => 'EgWKAQIoAWoKEAkQBRAKEAMQBA=='
        ]);

        $commParallel = apolloHttpParallel([
            'home' => [
                'url' => 'https://music.youtube.com/youtubei/v1/browse?prettyPrint=false',
                'method' => 'POST',
                'headers' => [
                    'Content-Type: application/json',
                    'Origin: https://music.youtube.com',
                    'Referer: https://music.youtube.com/'
                ],
                'body' => $homePayload,
                'timeout' => 4
            ],
            'seed' => [
                'url' => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
                'method' => 'POST',
                'headers' => [
                    'Content-Type: application/json',
                    'Origin: https://music.youtube.com',
                    'Referer: https://music.youtube.com/'
                ],
                'body' => $seedPayload,
                'timeout' => 4
            ]
        ], 4);

        $homeResp = $commParallel['home'] ?? ['code' => 0, 'body' => ''];
        $seedResp = $commParallel['seed'] ?? ['code' => 0, 'body' => ''];

        if ($homeResp['code'] === 200 && !empty($homeResp['body'])) {
            $homeData = json_decode($homeResp['body'], true);
            $sections = $homeData['contents']['singleColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];

            foreach ($sections as $sec) {
                $carousel = $sec['musicCarouselShelfRenderer'] ?? null;
                if (!$carousel) continue;

                foreach (($carousel['contents'] ?? []) as $item) {
                    $m = $item['musicTwoRowItemRenderer'] ?? null;
                    if (!$m) continue;

                    $browseId = $m['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
                    // Community playlists in YouTube Music use VLPL... browse IDs
                    if (empty($browseId) || strpos($browseId, 'VL') !== 0 || isset($seen[$browseId])) {
                        continue;
                    }

                    $title = '';
                    foreach (($m['title']['runs'] ?? []) as $r) {
                        $title .= ($r['text'] ?? '');
                    }
                    $title = trim($title);
                    if ($title === '') continue;

                    $subtitle = '';
                    foreach (($m['subtitle']['runs'] ?? []) as $sr) {
                        $subtitle .= ($sr['text'] ?? '');
                    }
                    $subtitle = trim($subtitle);

                    $thumbs = $m['thumbnailRenderer']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
                    $thumbUrl = '';
                    if (!empty($thumbs) && is_array($thumbs)) {
                        $lastT = end($thumbs);
                        $thumbUrl = upgradeCommThumb($lastT['url'] ?? '');
                    }
                    if ($thumbUrl === '') continue;

                    $seen[$browseId] = true;
                    $pool[] = [
                        'browse_id' => $browseId,
                        'title' => $title,
                        'creator' => $subtitle ?: 'YouTube Music Community',
                        'artwork' => $thumbUrl
                    ];
                }
            }
        }

        if ($seedResp['code'] === 200 && !empty($seedResp['body'])) {
            $seedData = json_decode($seedResp['body'], true);
            $sSecs = $seedData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
            foreach ($sSecs as $sec) {
                foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
                    $r = $item['musicResponsiveListItemRenderer'] ?? null;
                    if (!$r) continue;

                    $browseId = $r['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
                    if (empty($browseId) || strpos($browseId, 'VL') !== 0 || isset($seen[$browseId])) {
                        continue;
                    }

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
                    $thumbUrl = '';
                    if (!empty($thumbs) && is_array($thumbs)) {
                        $lastT = end($thumbs);
                        $thumbUrl = upgradeCommThumb($lastT['url'] ?? '');
                    }
                    if ($thumbUrl === '') continue;

                    $seen[$browseId] = true;
                    $pool[] = [
                        'browse_id' => $browseId,
                        'title' => $title,
                        'creator' => $subtitle ?: 'YouTube Music Community',
                        'artwork' => $thumbUrl
                    ];
                }
            }
        }

        if (!empty($pool)) {
            @file_put_contents($cacheFile, json_encode([
                'created_at' => time(),
                'items' => $pool
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    shuffle($pool);

    jsonResponse([
        'status' => 'ok',
        'playlists' => array_slice($pool, 0, 4)
    ]);
}

if ($action === 'tracks') {
    $browseId = trim($_GET['browse_id'] ?? '');
    if (empty($browseId) || !preg_match('/^[a-zA-Z0-9_\-]+$/', $browseId)) {
        jsonResponse(['status' => 'error', 'message' => 'Invalid browse_id'], 400);
    }

    $plCacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/apollo_ytm_comm_tracks_' . md5($browseId) . '.json';
    if (is_file($plCacheFile)) {
        $raw = @file_get_contents($plCacheFile);
        if (!empty($raw)) {
            $cached = json_decode($raw, true);
            if (is_array($cached) && !empty($cached['tracks']) && (time() - (int)($cached['created_at'] ?? 0)) < 1800) {
                jsonResponse([
                    'status' => 'ok',
                    'browse_id' => $browseId,
                    'tracks' => $cached['tracks']
                ]);
            }
        }
    }

    $payload = json_encode([
        'context' => [
            'client' => [
                'clientName' => 'WEB_REMIX',
                'clientVersion' => '1.20241028.01.00',
                'hl' => 'en',
                'gl' => 'ID'
            ]
        ],
        'browseId' => $browseId
    ]);

    $resp = apolloHttpRequest(
        'https://music.youtube.com/youtubei/v1/browse?prettyPrint=false',
        'POST',
        [
            'Content-Type: application/json',
            'Origin: https://music.youtube.com',
            'Referer: https://music.youtube.com/'
        ],
        $payload,
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
            if (isset($r['flexColumns'][2]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'])) {
                foreach ($r['flexColumns'][2]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] as $ar) {
                    $album .= ($ar['text'] ?? '');
                }
            }
            $album = trim($album);

            $durRaw = $r['fixedColumns'][0]['musicResponsiveListItemFixedColumnRenderer']['text']['runs'][0]['text'] ?? '';
            list($durSec, $formattedDur) = normalizeCommDuration($durRaw);
            if ($durSec > 720) continue; // Skip hour-long compilations inside community playlists

            $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
            $thumbUrl = '';
            if (!empty($thumbs) && is_array($thumbs)) {
                $lastT = end($thumbs);
                $thumbUrl = upgradeCommThumb($lastT['url'] ?? '');
            }
            if ($thumbUrl === '') {
                $thumbUrl = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
            }

            $seenVids[$vId] = true;
            $tracks[] = [
                'video_id' => $vId,
                'title' => $title,
                'artist' => $artist,
                'album' => $album,
                'duration' => $formattedDur,
                'thumbnail' => $thumbUrl,
                'source' => 'youtube_music',
                'is_official' => true
            ];

            if (count($tracks) >= 50) break;
        }
    }

    if (!empty($tracks)) {
        @file_put_contents($plCacheFile, json_encode([
            'created_at' => time(),
            'tracks' => $tracks
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    jsonResponse([
        'status' => 'ok',
        'browse_id' => $browseId,
        'tracks' => $tracks
    ]);
}

jsonResponse(['status' => 'error', 'message' => 'Unknown action'], 400);
