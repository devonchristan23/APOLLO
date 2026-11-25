<?php
/**
 * APOLLO — Service Mesin Pencari Lagu (Multi-Source Parallel Search)
 * Prioritas 1: Lagu resmi YouTube Music (dengan cover album persegi 544x544)
 * Prioritas 2: Lagu dari YouTube reguler / komunitas (ditempatkan di bawah lagu resmi)
 */

/**
 * Ekstrak hasil lagu resmi dari response JSON YouTube Music (WEB_REMIX)
 */
function apolloExtractYtmSearchTracks($ytmBody, $query, &$seenIds, &$officialTracks, &$unofficialTracks) {
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

            if (count($officialTracks) >= 14) {
                return;
            }
        }
    }
}

/**
 * Tambahan lagu resmi dari Piped Music Songs jika IP server kena pembatasan rate-limit
 */
function apolloExtractPipedMusicTracks($pipedBody, $query, &$seenIds, &$officialTracks) {
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

        if (count($officialTracks) >= 14) break;
    }
}

/**
 * Ekstrak video musik umum dari response JSON YouTube Web (WEB)
 */
function apolloExtractYoutubeWebTracks($ytWebBody, $query, &$seenIds, &$officialTracks, &$unofficialTracks) {
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

            // Lewati live stream tanpa durasi
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
                'album'       => $isTopicChannel ? 'YouTube Music Archive' : 'YouTube Video',
                'duration'    => $formattedDur,
                'thumbnail'   => $thumb,
                'source'      => $isTopicChannel ? 'youtube_music' : 'youtube',
                'is_official' => $isTopicChannel
            ];

            if ($isTopicChannel && count($officialTracks) < 14) {
                $officialTracks[] = $trackItem;
            } else {
                $unofficialTracks[] = $trackItem;
            }

            if (count($unofficialTracks) >= 10) {
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
    $cmd = escapeshellcmd($ytDlpBin) . ' --dump-json --flat-playlist --no-warnings ' . escapeshellarg("ytsearch25:{$musicTarget}") . ' 2>/dev/null';

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
            'album'       => $isTopic ? 'YouTube Music Archive' : 'YouTube Video',
            'duration'    => formatSeconds($durationSec),
            'thumbnail'   => $thumb,
            'source'      => $isTopic ? 'youtube_music' : 'youtube',
            'is_official' => $isTopic
        ];

        if ($isTopic) $officialTracks[] = $item;
        else $unofficialTracks[] = $item;

        if ((count($officialTracks) + count($unofficialTracks)) >= 20) break;
    }
}

/**
 * Fungsi utama pencarian lagu & artis
 */
function apolloSearchTracksAndArtists($query) {
    $musicTarget = buildMusicSearchQuery($query);
    $seenIds = [];
    $officialTracks = [];
    $unofficialTracks = [];

    // 1. Jalankan request YouTube Music + YouTube Web secara paralel (~1.0 - 1.4 detik)
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
        apolloExtractYtmSearchTracks($parallelRes['ytm']['body'], $query, $seenIds, $officialTracks, $unofficialTracks);
    }

    if (($parallelRes['ytweb']['code'] ?? 0) === 200 && !empty($parallelRes['ytweb']['body'])) {
        apolloExtractYoutubeWebTracks($parallelRes['ytweb']['body'], $query, $seenIds, $officialTracks, $unofficialTracks);
    }

    // Fallback ke Piped Music Songs hanya jika YouTube Music kosong (misal terkena rate-limit cloud)
    if (empty($officialTracks)) {
        $pipedSongsResp = apolloHttpRequest(
            'https://api.piped.private.coffee/search?q=' . urlencode($musicTarget) . '&filter=music_songs',
            'GET',
            [],
            null,
            3
        );
        if ($pipedSongsResp['code'] === 200 && !empty($pipedSongsResp['body'])) {
            apolloExtractPipedMusicTracks($pipedSongsResp['body'], $query, $seenIds, $officialTracks);
        }
    }

    if (empty($officialTracks) && empty($unofficialTracks)) {
        apolloFallbackYtDlpSearch($musicTarget, $query, $seenIds, $officialTracks, $unofficialTracks);
    }

    // 2. Gabungkan: lagu resmi YouTube Music di atas, video YouTube di bawah
    $results = array_slice(array_merge($officialTracks, $unofficialTracks), 0, 24);

    // 3. Susun maksimal 4 kartu artis dari hasil teratas
    $foundArtists = [];
    $artistsList = [];
    foreach ($results as $r) {
        $artName = trim($r['artist']);
        $lowName = strtolower($artName);
        if (!isset($foundArtists[$lowName]) && count($artistsList) < 4) {
            $foundArtists[$lowName] = true;
            $artistsList[] = [
                'name'     => strtoupper($artName),
                'key'      => $artName,
                'location' => !empty($r['is_official']) ? 'YOUTUBE MUSIC · OFFICIAL' : 'YOUTUBE ARCHIVE',
                'photo'    => $r['thumbnail'],
                'genre'    => !empty($r['album']) ? strtoupper($r['album']) : 'AUDIO RECORDING'
            ];
        }
    }

    return [
        'tracks'           => $results,
        'official_count'   => count($officialTracks),
        'unofficial_count' => count($unofficialTracks),
        'artists'          => $artistsList
    ];
}
