<?php
/**
 * Apollo Minimalist Music Player - Search Engine API
 * Priority 1: Official YouTube Music Songs (with square album artwork & album metadata)
 * Priority 2: Unofficial / General YouTube Results (placed below official YT Music tracks)
 */

require_once __DIR__ . '/../config/db.php';

$searchUser = apolloGetCurrentUser();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$q = trim($_GET['q'] ?? '');

/**
 * Convert "M:SS" or "H:MM:SS" duration text to seconds & normalized "MM:SS"
 */
function parseDurationText($durText) {
    $durText = trim((string)$durText);
    if (empty($durText) || strpos($durText, ':') === false) {
        return [0, '03:30'];
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
    return [0, '03:30'];
}

/**
 * Parse a YouTube Music responsive list item renderer into an APOLLO track array
 */
function parseYtmResponsiveTrack($r, $filterQuery = '') {
    $vId = $r['playlistItemData']['videoId'] ?? ($r['navigationEndpoint']['watchEndpoint']['videoId'] ?? '');
    if (empty($vId)) return null;

    $titleRuns = $r['flexColumns'][0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? [];
    $rawTitle = '';
    foreach ($titleRuns as $tr) {
        $rawTitle .= ($tr['text'] ?? '');
    }
    $rawTitle = trim($rawTitle);
    if (empty($rawTitle)) return null;

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

    $artistName = $filteredSegments[0] ?? 'YouTube Music Artist';
    $albumName = $filteredSegments[1] ?? $rawTitle;
    list($durationSec, $formattedDur) = parseDurationText($durationText);

    // Validate title + artist + album together so compilation/playlist/greatest-hits albums are rejected
    if (!isLegitimateMusicTrack($rawTitle . ' ' . $albumName, $durationSec, $artistName . ' - Topic', $filterQuery)) {
        return null;
    }

    $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
    $thumbUrl = '';
    if (!empty($thumbs) && is_array($thumbs)) {
        $lastThumb = end($thumbs);
        $thumbUrl = $lastThumb['url'] ?? '';
    }
    if (empty($thumbUrl)) return null;

    if (strpos($thumbUrl, 'googleusercontent.com') !== false || strpos($thumbUrl, 'ggpht.com') !== false) {
        $thumbUrl = preg_replace('/=w\d+-h\d+[^&?]*/', '=w544-h544-l90-rj', $thumbUrl);
    }

    return [
        'video_id' => $vId,
        'title' => $rawTitle,
        'artist' => $artistName,
        'album' => $albumName,
        'duration' => $formattedDur,
        'thumbnail' => $thumbUrl,
        'source' => 'youtube_music',
        'is_official' => true
    ];
}

/**
 * Fetch Live Recommended Individual Songs directly from YouTube Music
 */
function getLiveYoutubeMusicRecommendations() {
    $cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/apollo_ytm_home_songs_v3.json';
    $pool = [];

    if (is_file($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if (!empty($raw)) {
            $cached = json_decode($raw, true);
            if (is_array($cached) && !empty($cached['tracks']) && (time() - (int)($cached['created_at'] ?? 0)) < 600) {
                $pool = $cached['tracks'];
            }
        }
    }

    if (empty($pool)) {
        $seen = [];

        // Fetch individual songs from real Indonesian & global artists via YouTube Music Songs catalog
        // (avoids generic "greatest hits / playlist / collection" spam)
        $artistSeeds = [
            'Barasuara', 'Hindia', 'Perunggu', 'Tulus', 'Sheila On 7',
            'Bernadya', 'Juicy Luicy', 'Nadin Amizah', 'Payung Teduh', 'Dewa 19',
            'Arctic Monkeys', 'The Weeknd', 'Coldplay', 'Radiohead', 'Bruno Mars',
            'Daniel Caesar', 'Daft Punk', 'Oasis', 'Billie Eilish', 'Queen'
        ];
        shuffle($artistSeeds);

        $pickedSeeds = array_slice($artistSeeds, 0, 2);
        $seedRequests = [];
        foreach ($pickedSeeds as $idx => $seedArtist) {
            $seedRequests['seed_' . $idx] = [
                'url' => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
                'method' => 'POST',
                'headers' => [
                    'Content-Type: application/json',
                    'Origin: https://music.youtube.com',
                    'Referer: https://music.youtube.com/'
                ],
                'body' => json_encode([
                    'context' => [
                        'client' => [
                            'clientName' => 'WEB_REMIX',
                            'clientVersion' => '1.20241028.01.00',
                            'hl' => 'en',
                            'gl' => 'ID'
                        ]
                    ],
                    'query' => $seedArtist,
                    'params' => 'EgWKAQIIAWoKEAkQBRAKEAMQBA=='
                ]),
                'timeout' => 4
            ];
        }

        $seedResponses = apolloHttpParallel($seedRequests, 4);
        foreach ($seedResponses as $seedResp) {
            if ($seedResp['code'] === 200 && !empty($seedResp['body'])) {
                $seedData = json_decode($seedResp['body'], true);
                $sSecs = $seedData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
                $perArtistCount = 0;
                foreach ($sSecs as $sec) {
                    foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
                        if (!empty($item['musicResponsiveListItemRenderer'])) {
                            $parsed = parseYtmResponsiveTrack($item['musicResponsiveListItemRenderer']);
                            if ($parsed && !isset($seen[$parsed['video_id']])) {
                                $seen[$parsed['video_id']] = true;
                                $pool[] = $parsed;
                                $perArtistCount++;
                                if ($perArtistCount >= 8) break 2;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($pool)) {
            @file_put_contents($cacheFile, json_encode([
                'created_at' => time(),
                'tracks' => $pool
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    shuffle($pool);

    // Build 4 distinct-artist Recommended Individual Song Cards (showing Song Title + Artist, NOT compilation/playlist)
    $recommendedSongs = [];
    $usedArtists = [];
    foreach ($pool as $t) {
        $artKey = strtolower(trim($t['artist']));
        if (isset($usedArtists[$artKey])) continue;
        $usedArtists[$artKey] = true;
        $recommendedSongs[] = [
            'title' => $t['title'],
            'artist' => $t['artist'],
            'badge' => $t['duration'] ?: 'SONG',
            'artwork' => $t['thumbnail'],
            'leadTrack' => $t
        ];
        if (count($recommendedSongs) >= 4) break;
    }

    return [
        'albums' => $recommendedSongs,
        'tracks' => array_slice($pool, 0, 14)
    ];
}

if (empty($q)) {
    $liveRecs = getLiveYoutubeMusicRecommendations();
    jsonResponse([
        'status' => 'ok',
        'is_recommendations' => true,
        'recommended_albums' => $liveRecs['albums'],
        'tracks' => $liveRecs['tracks'],
        'artists' => []
    ]);
}

$musicTarget = buildMusicSearchQuery($q);
$seenIds = [];
$officialYtMusicTracks = [];
$unofficialYoutubeTracks = [];

// =========================================================================
// PARALLEL FETCH: TIER 1 (YOUTUBE MUSIC) + TIER 2 (YOUTUBE WEB) + PIPED BACKUP
// Finishes in ~1.2s total so Vercel never hits 10s serverless timeout
// =========================================================================
$ytmPayload = json_encode([
    'context' => [
        'client' => [
            'clientName' => 'WEB_REMIX',
            'clientVersion' => '1.20241028.01.00',
            'hl' => 'en',
            'gl' => 'ID'
        ]
    ],
    'query' => $musicTarget,
    'params' => 'EgWKAQIIAWoKEAkQBRAKEAMQBA=='
]);

$ytWebPayload = json_encode([
    'context' => [
        'client' => [
            'clientName' => 'WEB',
            'clientVersion' => '2.20241028.01.00',
            'hl' => 'en',
            'gl' => 'ID'
        ]
    ],
    'query' => $musicTarget
]);

$parallelRes = apolloHttpParallel([
    'ytm' => [
        'url' => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
        'method' => 'POST',
        'headers' => [
            'Content-Type: application/json',
            'Origin: https://music.youtube.com',
            'Referer: https://music.youtube.com/'
        ],
        'body' => $ytmPayload,
        'timeout' => 4
    ],
    'ytweb' => [
        'url' => 'https://www.youtube.com/youtubei/v1/search?prettyPrint=false',
        'method' => 'POST',
        'headers' => [
            'Content-Type: application/json',
            'Origin: https://www.youtube.com',
            'Referer: https://www.youtube.com/'
        ],
        'body' => $ytWebPayload,
        'timeout' => 4
    ],
    'piped_songs' => [
        'url' => 'https://api.piped.private.coffee/search?q=' . urlencode($musicTarget) . '&filter=music_songs',
        'method' => 'GET',
        'timeout' => 4
    ]
], 4);

$ytmResp = $parallelRes['ytm'] ?? ['code' => 0, 'body' => ''];
$ytWebResp = $parallelRes['ytweb'] ?? ['code' => 0, 'body' => ''];
$pipedSongsResp = $parallelRes['piped_songs'] ?? ['code' => 0, 'body' => ''];

if ($ytmResp['code'] === 200 && !empty($ytmResp['body'])) {
    $ytmData = json_decode($ytmResp['body'], true);
    $sections = $ytmData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];

    foreach ($sections as $sec) {
        $items = $sec['musicShelfRenderer']['contents'] ?? [];
        foreach ($items as $item) {
            $r = $item['musicResponsiveListItemRenderer'] ?? null;
            if (!$r) continue;

            $vId = $r['playlistItemData']['videoId'] ?? '';
            if (empty($vId) || isset($seenIds[$vId])) continue;

            $titleRuns = $r['flexColumns'][0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? [];
            $rawTitle = '';
            foreach ($titleRuns as $tr) {
                $rawTitle .= ($tr['text'] ?? '');
            }
            $rawTitle = trim($rawTitle);
            if (empty($rawTitle)) continue;

            // Parse Artist • Album • Duration segments from flexColumns[1]
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

            // Strip leading type markers like "Song" if present
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

            $artistName = $filteredSegments[0] ?? 'Unknown Artist';
            $albumName = $filteredSegments[1] ?? 'YouTube Music Single';
            list($durationSec, $formattedDur) = parseDurationText($durationText);

            if (!isLegitimateMusicTrack($rawTitle, $durationSec, $artistName . ' - Topic', $q)) {
                continue;
            }

            // Extract high-resolution square album cover from YouTube Music
            $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
            $thumbUrl = '';
            if (!empty($thumbs) && is_array($thumbs)) {
                $lastThumb = end($thumbs);
                $thumbUrl = $lastThumb['url'] ?? '';
            }
            $hasAlbumArt = false;
            if (!empty($thumbUrl)) {
                // Upgrade googleusercontent album art to crisp 544x544 square cover
                if (strpos($thumbUrl, 'googleusercontent.com') !== false || strpos($thumbUrl, 'ggpht.com') !== false) {
                    $thumbUrl = preg_replace('/=w\d+-h\d+[^&?]*/', '=w544-h544-l90-rj', $thumbUrl);
                    $hasAlbumArt = true;
                }
            } else {
                $thumbUrl = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
            }

            $seenIds[$vId] = true;
            $entryData = [
                'video_id' => $vId,
                'title' => $rawTitle,
                'artist' => $artistName,
                'album' => $albumName,
                'duration' => $formattedDur,
                'thumbnail' => $thumbUrl,
                'source' => $hasAlbumArt ? 'youtube_music' : 'youtube',
                'is_official' => $hasAlbumArt
            ];

            if ($hasAlbumArt) {
                $officialYtMusicTracks[] = $entryData;
            } else {
                $unofficialYoutubeTracks[] = $entryData;
            }

            if (count($officialYtMusicTracks) >= 14) {
                break 2;
            }
        }
    }
}

// Supplement official YT Music results from Piped Music Songs if YT Music was rate-limited on cloud IP
if (count($officialYtMusicTracks) < 6 && $pipedSongsResp['code'] === 200 && !empty($pipedSongsResp['body'])) {
    $pipedData = json_decode($pipedSongsResp['body'], true);
    foreach (($pipedData['items'] ?? []) as $pItem) {
        $vId = '';
        if (!empty($pItem['url'])) {
            parse_str((string)parse_url($pItem['url'], PHP_URL_QUERY), $qArgs);
            $vId = $qArgs['v'] ?? str_replace('/watch?v=', '', $pItem['url']);
        }
        if (empty($vId) || isset($seenIds[$vId])) continue;
        $rawTitle = trim($pItem['title'] ?? '');
        $uploader = preg_replace('/\s*-\s*Topic$/i', '', trim($pItem['uploaderName'] ?? ''));
        $durSec = (int)($pItem['duration'] ?? 0);
        if ($rawTitle === '' || !isLegitimateMusicTrack($rawTitle, $durSec, $uploader . ' - Topic', $q)) continue;

        $thumb = $pItem['thumbnail'] ?? "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
        if (strpos($thumb, 'googleusercontent.com') !== false || strpos($thumb, 'ggpht.com') !== false) {
            $thumb = preg_replace('/=w\d+-h\d+[^&?]*/', '=w544-h544-l90-rj', $thumb);
        }
        $seenIds[$vId] = true;
        $officialYtMusicTracks[] = [
            'video_id' => $vId,
            'title' => $rawTitle,
            'artist' => $uploader ?: 'Unknown Artist',
            'album' => 'YouTube Music',
            'duration' => formatSeconds($durSec),
            'thumbnail' => $thumb,
            'source' => 'youtube_music',
            'is_official' => true
        ];
        if (count($officialYtMusicTracks) >= 14) break;
    }
}

// =========================================================================
// TIER 2 (SECONDARY / BELOW): YOUTUBE GENERAL RESULTS (UNOFFICIAL / VIDEOS)
// =========================================================================
if ($ytWebResp['code'] === 200 && !empty($ytWebResp['body'])) {
    $ytWebData = json_decode($ytWebResp['body'], true);
    $ytSections = $ytWebData['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];

    foreach ($ytSections as $sec) {
        $items = $sec['itemSectionRenderer']['contents'] ?? [];
        foreach ($items as $item) {
            $v = $item['videoRenderer'] ?? null;
            if (!$v) continue;

            $vId = $v['videoId'] ?? '';
            if (empty($vId) || isset($seenIds[$vId])) continue;

            $rawTitle = '';
            foreach (($v['title']['runs'] ?? []) as $tr) {
                $rawTitle .= ($tr['text'] ?? '');
            }
            $rawTitle = trim($rawTitle);
            $uploader = $v['ownerText']['runs'][0]['text'] ?? ($v['longBylineText']['runs'][0]['text'] ?? '');
            $lenText = $v['lengthText']['simpleText'] ?? '';

            // Skip live streams without duration
            if (empty($lenText)) continue;

            list($durationSec, $formattedDur) = parseDurationText($lenText);
            if (!isLegitimateMusicTrack($rawTitle, $durationSec, $uploader, $q)) {
                continue;
            }

            $isTopicChannel = (stripos($uploader, '- Topic') !== false);
            list($cleanTitle, $cleanArtist) = cleanMusicDetails($rawTitle, $uploader);

            $thumb = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
            if (!empty($v['thumbnail']['thumbnails']) && is_array($v['thumbnail']['thumbnails'])) {
                $lastT = end($v['thumbnail']['thumbnails']);
                if (!empty($lastT['url'])) {
                    $thumb = $lastT['url'];
                }
            }

            $seenIds[$vId] = true;
            $trackItem = [
                'video_id' => $vId,
                'title' => $cleanTitle,
                'artist' => $cleanArtist,
                'album' => $isTopicChannel ? 'YouTube Music Archive' : 'YouTube Video',
                'duration' => $formattedDur,
                'thumbnail' => $thumb,
                'source' => $isTopicChannel ? 'youtube_music' : 'youtube',
                'is_official' => $isTopicChannel
            ];

            if ($isTopicChannel && count($officialYtMusicTracks) < 14) {
                $officialYtMusicTracks[] = $trackItem;
            } else {
                $unofficialYoutubeTracks[] = $trackItem;
            }

            if (count($unofficialYoutubeTracks) >= 10) {
                break 2;
            }
        }
    }
}

// =========================================================================
// FALLBACK: bin/yt-dlp ONLY on local non-Vercel environments
// =========================================================================
$isVercelRuntime = (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false);
if (!$isVercelRuntime && empty($officialYtMusicTracks) && empty($unofficialYoutubeTracks) && (function_exists('exec') || function_exists('shell_exec'))) {
    $ytDlpBin = getYtDlpBinary();
    $searchTarget = "ytsearch25:{$musicTarget}";
    $cmd = escapeshellcmd($ytDlpBin) . " --dump-json --flat-playlist --no-warnings " . escapeshellarg($searchTarget) . " 2>/dev/null";

    $lines = [];
    @exec($cmd, $lines);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] !== '{') continue;
        $entry = json_decode($line, true);
        if (!is_array($entry) || empty($entry['id'])) continue;

        $vId = $entry['id'];
        if (isset($seenIds[$vId])) continue;

        $rawTitle = $entry['title'] ?? 'Unknown Signal';
        $uploader = $entry['uploader'] ?? $entry['channel'] ?? '';
        $durationSec = (int)($entry['duration'] ?? 0);

        if (!isLegitimateMusicTrack($rawTitle, $durationSec, $uploader, $q)) {
            continue;
        }

        $isTopic = (stripos($uploader, '- Topic') !== false);
        list($cleanTitle, $cleanArtist) = cleanMusicDetails($rawTitle, $uploader);
        $dur = formatSeconds($durationSec);

        $thumb = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
        if (!empty($entry['thumbnails']) && is_array($entry['thumbnails'])) {
            $lastThumb = end($entry['thumbnails']);
            if (!empty($lastThumb['url'])) {
                $thumb = $lastThumb['url'];
            }
        }

        $seenIds[$vId] = true;
        $item = [
            'video_id' => $vId,
            'title' => $cleanTitle,
            'artist' => $cleanArtist,
            'album' => $isTopic ? 'YouTube Music Archive' : 'YouTube Video',
            'duration' => $dur,
            'thumbnail' => $thumb,
            'source' => $isTopic ? 'youtube_music' : 'youtube',
            'is_official' => $isTopic
        ];

        if ($isTopic) {
            $officialYtMusicTracks[] = $item;
        } else {
            $unofficialYoutubeTracks[] = $item;
        }

        if ((count($officialYtMusicTracks) + count($unofficialYoutubeTracks)) >= 20) {
            break;
        }
    }
}

// Strictly place YouTube Music official album tracks at TOP, unofficial YouTube videos BELOW
$results = array_merge($officialYtMusicTracks, $unofficialYoutubeTracks);
$results = array_slice($results, 0, 24);

// Build dynamic artist cards preferring official album art
$foundArtists = [];
$artistsList = [];
foreach ($results as $r) {
    $artName = trim($r['artist']);
    $lowName = strtolower($artName);
    if (!isset($foundArtists[$lowName]) && count($artistsList) < 4) {
        $foundArtists[$lowName] = true;
        $artistsList[] = [
            'name' => strtoupper($artName),
            'key' => $artName,
            'location' => !empty($r['is_official']) ? 'YOUTUBE MUSIC · OFFICIAL' : 'YOUTUBE ARCHIVE',
            'photo' => $r['thumbnail'],
            'genre' => !empty($r['album']) ? strtoupper($r['album']) : 'AUDIO RECORDING'
        ];
    }
}

// Log user/visitor search query to MySQL activity_logs
apolloLogActivity('SEARCH', '', '', '', "Query: {$q} (" . count($results) . " results)", $searchUser);

jsonResponse([
    'status' => 'ok',
    'query' => $q,
    'total' => count($results),
    'official_count' => count($officialYtMusicTracks),
    'unofficial_count' => count($unofficialYoutubeTracks),
    'engine' => 'youtube_music+youtube',
    'tracks' => $results,
    'artists' => $artistsList
]);
