<?php
/**
 * APOLLO — Service Mesin Pencari Lagu, Album Resmi, Artis, & Playlist Komunitas
 * Mendukung 4 tab pencarian: Tracks | Albums | Artists | Playlists
 */

/**
 * Ekstrak hasil lagu resmi dari response JSON YouTube Music (WEB_REMIX)
 */
function apolloExtractYtmSearchTracks($ytmBody, $query, &$seenIds, &$officialTracks, &$unofficialTracks, $maxOfficial = 6) {
    $ytmData = json_decode($ytmBody, true);
    $sections = $ytmData['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];

    foreach ($sections as $sec) {
        foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
            if (empty($item['musicResponsiveListItemRenderer'])) continue;

            $parsed = apolloParseYtmResponsiveTrack($item['musicResponsiveListItemRenderer'], $query, [
                'require_album_art'   => false,
                'strict_album_filter' => false,
                'default_artist'      => 'Unknown Artist',
                'default_album'       => 'YouTube Music Single'
            ]);

            if (!$parsed || isset($seenIds[$parsed['video_id']])) continue;
            $seenIds[$parsed['video_id']] = true;

            if ($parsed['is_official']) {
                $officialTracks[] = $parsed;
            } else {
                $unofficialTracks[] = $parsed;
            }

            if (count($officialTracks) >= $maxOfficial) {
                return;
            }
        }
    }
}

/**
 * Tambahan lagu resmi dari Piped Music Songs jika IP server kena pembatasan rate-limit
 */
function apolloExtractPipedMusicTracks($pipedBody, $query, &$seenIds, &$officialTracks, $maxOfficial = 6) {
    $pipedData = json_decode($pipedBody, true);
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

        if ($rawTitle === '' || !isLegitimateMusicTrack($rawTitle, $durSec, $uploader . ' - Topic', $query)) {
            continue;
        }

        $thumb = apolloUpgradeYtmThumb($pItem['thumbnail'] ?? "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg");
        $seenIds[$vId] = true;

        $officialTracks[] = [
            'video_id'    => $vId,
            'title'       => $rawTitle,
            'artist'      => $uploader ?: 'Unknown Artist',
            'album'       => 'YouTube Music',
            'duration'    => formatSeconds($durSec),
            'thumbnail'   => $thumb,
            'source'      => 'youtube_music',
            'is_official' => true
        ];

        if (count($officialTracks) >= $maxOfficial) break;
    }
}

/**
 * Ekstrak video musik dari response JSON YouTube Web (WEB)
 */
function apolloExtractYoutubeWebTracks($ytWebBody, $query, &$seenIds, &$officialTracks, &$unofficialTracks, $maxOfficial = 6, $maxYoutube = 10) {
    $ytWebData = json_decode($ytWebBody, true);
    $ytSections = $ytWebData['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];

    foreach ($ytSections as $sec) {
        foreach (($sec['itemSectionRenderer']['contents'] ?? []) as $item) {
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

            if (empty($lenText)) continue;

            list($durationSec, $formattedDur) = apolloParseDurationText($lenText);
            if (!isLegitimateMusicTrack($rawTitle, $durationSec, $uploader, $query)) {
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
                'video_id'    => $vId,
                'title'       => $cleanTitle,
                'artist'      => $cleanArtist,
                'album'       => $isTopicChannel ? 'YouTube Music' : 'Video dari YouTube',
                'duration'    => $formattedDur,
                'thumbnail'   => $thumb,
                'source'      => $isTopicChannel ? 'youtube_music' : 'youtube',
                'is_official' => $isTopicChannel
            ];

            if ($isTopicChannel && count($officialTracks) < $maxOfficial) {
                $officialTracks[] = $trackItem;
            } else {
                $unofficialTracks[] = $trackItem;
            }

            if (count($unofficialTracks) >= $maxYoutube) {
                return;
            }
        }
    }
}

/**
 * Fallback pencarian lokal memakai bin/yt-dlp (khusus non-Vercel)
 */
function apolloFallbackYtDlpSearch($musicTarget, $query, &$seenIds, &$officialTracks, &$unofficialTracks) {
    if (apolloIsVercelRuntime() || (!function_exists('exec') && !function_exists('shell_exec'))) {
        return;
    }

    $ytDlpBin = getYtDlpBinary();
    $cmd = escapeshellcmd($ytDlpBin) . ' --dump-json --flat-playlist --no-warnings ' . escapeshellarg("ytsearch20:{$musicTarget}") . ' 2>/dev/null';

    $lines = [];
    @exec($cmd, $lines);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '{') continue;

        $entry = json_decode($line, true);
        if (!is_array($entry) || empty($entry['id'])) continue;

        $vId = $entry['id'];
        if (isset($seenIds[$vId])) continue;

        $rawTitle = $entry['title'] ?? 'Unknown Signal';
        $uploader = $entry['uploader'] ?? $entry['channel'] ?? '';
        $durationSec = (int)($entry['duration'] ?? 0);

        if (!isLegitimateMusicTrack($rawTitle, $durationSec, $uploader, $query)) {
            continue;
        }

        $isTopic = (stripos($uploader, '- Topic') !== false);
        list($cleanTitle, $cleanArtist) = cleanMusicDetails($rawTitle, $uploader);

        $thumb = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
        if (!empty($entry['thumbnails']) && is_array($entry['thumbnails'])) {
            $lastThumb = end($entry['thumbnails']);
            if (!empty($lastThumb['url'])) $thumb = $lastThumb['url'];
        }

        $seenIds[$vId] = true;
        $item = [
            'video_id'    => $vId,
            'title'       => $cleanTitle,
            'artist'      => $cleanArtist,
            'album'       => $isTopic ? 'YouTube Music' : 'Video dari YouTube',
            'duration'    => formatSeconds($durationSec),
            'thumbnail'   => $thumb,
            'source'      => $isTopic ? 'youtube_music' : 'youtube',
            'is_official' => $isTopic
        ];

        if ($isTopic) $officialTracks[] = $item;
        else $unofficialTracks[] = $item;

        if ((count($officialTracks) + count($unofficialTracks)) >= 16) break;
    }
}

/**
 * TAB 1: Pencarian Lagu (Tracks / Songs — YouTube Music di atas, Video YouTube tepat di bawahnya)
 */
function apolloSearchTracksAndArtists($query) {
    $musicTarget = buildMusicSearchQuery($query);
    $seenIds = [];
    $officialTracks = [];
    $unofficialTracks = [];

    // Batasi 6 lagu resmi di blok atas agar video dari YouTube langsung terlihat dekat di bawahnya
    $maxOfficial = 6;
    $maxYoutube  = 10;

    $parallelRes = apolloHttpParallel([
        'ytm' => [
            'url'     => 'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
            'method'  => 'POST',
            'headers' => apolloInnertubeHeaders('https://music.youtube.com'),
            'body'    => json_encode([
                'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
                'query'   => $musicTarget,
                'params'  => 'EgWKAQIIAWoKEAkQBRAKEAMQBA=='
            ]),
            'timeout' => 5
        ],
        'ytweb' => [
            'url'     => 'https://www.youtube.com/youtubei/v1/search?prettyPrint=false',
            'method'  => 'POST',
            'headers' => apolloInnertubeHeaders('https://www.youtube.com'),
            'body'    => json_encode([
                'context' => apolloBuildInnertubeContext('WEB', '2.20241028.01.00'),
                'query'   => $musicTarget
            ]),
            'timeout' => 5
        ]
    ], 5);

    if (($parallelRes['ytm']['code'] ?? 0) === 200 && !empty($parallelRes['ytm']['body'])) {
        apolloExtractYtmSearchTracks($parallelRes['ytm']['body'], $query, $seenIds, $officialTracks, $unofficialTracks, $maxOfficial);
    }

    if (($parallelRes['ytweb']['code'] ?? 0) === 200 && !empty($parallelRes['ytweb']['body'])) {
        apolloExtractYoutubeWebTracks($parallelRes['ytweb']['body'], $query, $seenIds, $officialTracks, $unofficialTracks, $maxOfficial, $maxYoutube);
    }

    if (empty($officialTracks)) {
        $pipedSongsResp = apolloHttpRequest(
            'https://api.piped.private.coffee/search?q=' . urlencode($musicTarget) . '&filter=music_songs',
            'GET',
            [],
            null,
            3
        );
        if ($pipedSongsResp['code'] === 200 && !empty($pipedSongsResp['body'])) {
            apolloExtractPipedMusicTracks($pipedSongsResp['body'], $query, $seenIds, $officialTracks, $maxOfficial);
        }
    }

    if (empty($officialTracks) && empty($unofficialTracks)) {
        apolloFallbackYtDlpSearch($musicTarget, $query, $seenIds, $officialTracks, $unofficialTracks);
    }

    $results = array_slice(array_merge($officialTracks, $unofficialTracks), 0, 18);

    return [
        'tracks'           => $results,
        'official_count'   => count($officialTracks),
        'unofficial_count' => count($unofficialTracks)
    ];
}

/**
 * Helper: Parse daftar Album Resmi dari body JSON YouTube Music (params: EgWKAQIYAWoKEAkQBRAKEAMQBA==)
 */
function apolloParseYtmAlbumsFromBody($body, $limit = 16) {
    $data = json_decode($body, true);
    $sections = $data['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
    $albums = [];
    $seen = [];

    foreach ($sections as $sec) {
        foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
            $r = $item['musicResponsiveListItemRenderer'] ?? null;
            if (!$r) continue;

            $browseId = $r['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
            if ($browseId === '' || isset($seen[$browseId])) continue;
            // Pastikan hanya rilis musik resmi (MPREb_ / OLAK5uy_), bukan podcast/show
            if (strpos($browseId, 'MPREb_') !== 0 && strpos($browseId, 'OLAK5uy_') !== 0) continue;

            $title = '';
            foreach (($r['flexColumns'][0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $tr) {
                $title .= ($tr['text'] ?? '');
            }
            $title = trim($title);
            if ($title === '') continue;

            $segments = [];
            $cur = '';
            foreach (($r['flexColumns'][1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $mr) {
                $txt = $mr['text'] ?? '';
                if (trim($txt) === '•' || $txt === ' • ') {
                    if (trim($cur) !== '') $segments[] = trim($cur);
                    $cur = '';
                } else {
                    $cur .= $txt;
                }
            }
            if (trim($cur) !== '') $segments[] = trim($cur);

            $releaseType = $segments[0] ?? 'Album';
            $artist      = $segments[1] ?? 'Various Artists';
            $year        = $segments[2] ?? '';

            // Buang konten podcast / wawancara / talkshow yang menyamar jadi rilis audio
            if (preg_match('/\b(podcast|wawancanda|interview|wawancara|talk\s*show|audiobook|episode\s*\d+|obrolan|ceramah|kajian)\b/i', $title . ' ' . $artist . ' ' . $releaseType)) {
                continue;
            }

            $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
            $lastT = (!empty($thumbs) && is_array($thumbs)) ? end($thumbs) : null;
            $artwork = apolloUpgradeYtmThumb($lastT['url'] ?? '');
            if ($artwork === '') continue;

            $seen[$browseId] = true;
            $albums[] = [
                'browse_id'    => $browseId,
                'title'        => $title,
                'artist'       => $artist,
                'year'         => $year,
                'release_type' => strtoupper($releaseType),
                'artwork'      => $artwork
            ];

            if (count($albums) >= $limit) break 2;
        }
    }

    // Prioritaskan Full Album & EP di atas Single
    usort($albums, function ($a, $b) {
        $rank = function ($t) {
            if ($t === 'ALBUM') return 0;
            if ($t === 'EP') return 1;
            return 2;
        };
        return $rank($a['release_type']) <=> $rank($b['release_type']);
    });

    return $albums;
}

/**
 * Helper: Parse daftar Artis dari body JSON YouTube Music (params: EgWKAQIgAWoKEAkQBRAKEAMQBA==)
 */
function apolloParseYtmArtistsFromBody($body, $limit = 16) {
    $data = json_decode($body, true);
    $sections = $data['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
    $artists = [];
    $seen = [];

    foreach ($sections as $sec) {
        foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
            $r = $item['musicResponsiveListItemRenderer'] ?? null;
            if (!$r) continue;

            $browseId = $r['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
            $name = '';
            foreach (($r['flexColumns'][0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $tr) {
                $name .= ($tr['text'] ?? '');
            }
            $name = trim($name);
            if ($name === '') continue;

            $dedupeKey = $browseId !== '' ? $browseId : strtolower($name);
            if (isset($seen[$dedupeKey])) continue;

            $subtitle = '';
            foreach (($r['flexColumns'][1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? []) as $mr) {
                $subtitle .= ($mr['text'] ?? '');
            }
            $subtitle = trim(preg_replace('/^Artist\s*•\s*/i', '', $subtitle));

            $thumbs = $r['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? [];
            $lastT = (!empty($thumbs) && is_array($thumbs)) ? end($thumbs) : null;
            $photo = apolloUpgradeYtmThumb($lastT['url'] ?? '');

            $seen[$dedupeKey] = true;
            $artists[] = [
                'browse_id' => $browseId,
                'name'      => $name,
                'subtitle'  => $subtitle ?: 'YouTube Music Artist',
                'photo'     => $photo
            ];

            if (count($artists) >= $limit) break 2;
        }
    }

    return $artists;
}

/**
 * TAB 2: Pencarian Album Resmi dari Band / Artis
 */
function apolloSearchAlbums($query, $limit = 16) {
    $musicTarget = buildMusicSearchQuery($query);
    $resp = apolloHttpRequest(
        'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
        'POST',
        apolloInnertubeHeaders('https://music.youtube.com'),
        json_encode([
            'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
            'query'   => $musicTarget,
            'params'  => 'EgWKAQIYAWoKEAkQBRAKEAMQBA=='
        ]),
        5
    );
    if ($resp['code'] !== 200 || empty($resp['body'])) return [];
    return apolloParseYtmAlbumsFromBody($resp['body'], $limit);
}

/**
 * TAB 3: Pencarian Artis yang Namanya Sesuai Kata Kunci
 */
function apolloSearchArtists($query, $limit = 16) {
    $musicTarget = buildMusicSearchQuery($query);
    $resp = apolloHttpRequest(
        'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
        'POST',
        apolloInnertubeHeaders('https://music.youtube.com'),
        json_encode([
            'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
            'query'   => $musicTarget,
            'params'  => 'EgWKAQIgAWoKEAkQBRAKEAMQBA=='
        ]),
        5
    );
    if ($resp['code'] !== 200 || empty($resp['body'])) return [];
    return apolloParseYtmArtistsFromBody($resp['body'], $limit);
}

/**
 * TAB 4: Pencarian Playlist Komunitas YouTube Music
 */
function apolloSearchCommunityPlaylists($query, $limit = 16) {
    $musicTarget = buildMusicSearchQuery($query);
    $resp = apolloHttpRequest(
        'https://music.youtube.com/youtubei/v1/search?prettyPrint=false',
        'POST',
        apolloInnertubeHeaders('https://music.youtube.com'),
        json_encode([
            'context' => apolloBuildInnertubeContext('WEB_REMIX', '1.20241028.01.00'),
            'query'   => $musicTarget,
            'params'  => 'EgWKAQIoAWoKEAkQBRAKEAMQBA=='
        ]),
        5
    );
    if ($resp['code'] !== 200 || empty($resp['body'])) return [];

    $data = json_decode($resp['body'], true);
    $sections = $data['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? [];
    $playlists = [];
    $seen = [];

    foreach ($sections as $sec) {
        foreach (($sec['musicShelfRenderer']['contents'] ?? []) as $item) {
            $r = $item['musicResponsiveListItemRenderer'] ?? null;
            if (!$r) continue;

            $browseId = $r['navigationEndpoint']['browseEndpoint']['browseId'] ?? '';
            if ($browseId === '' || isset($seen[$browseId])) continue;

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
            $artwork = apolloUpgradeYtmThumb($lastT['url'] ?? '');
            if ($artwork === '') continue;

            $seen[$browseId] = true;
            $playlists[] = [
                'browse_id' => $browseId,
                'title'     => $title,
                'creator'   => $subtitle ?: 'YouTube Music Community',
                'artwork'   => $artwork
            ];

            if (count($playlists) >= $limit) break 2;
        }
    }

    return $playlists;
}
