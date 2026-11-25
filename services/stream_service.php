<?php
/**
 * APOLLO — Service Ekstraktor Audio & Byte-Range Proxy Streaming
 * Menangani resolusi stream lokal (yt-dlp / Piped / Invidious) serta proxy audio anti-403.
 */

/**
 * Lokasi file cache metadata stream untuk sebuah video ID
 */
function apolloGetStreamCachePath($videoId) {
    $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $videoId);
    return rtrim(sys_get_temp_dir(), '/\\') . '/apollo_stream_v2_' . $safeId . '.json';
}

/**
 * Resolusi URL stream audio langsung beserta HTTP header yang dibutuhkan upstream
 */
function apolloResolveAudioStream($videoId, $forceRefresh = false, $cacheTtlSeconds = 2700) {
    $cacheFile = apolloGetStreamCachePath($videoId);

    if (!$forceRefresh && is_file($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if (!empty($raw)) {
            $cached = json_decode($raw, true);
            if (
                is_array($cached) &&
                !empty($cached['stream_url']) &&
                !empty($cached['created_at']) &&
                (time() - (int)$cached['created_at']) < $cacheTtlSeconds
            ) {
                return $cached;
            }
        }
    }

    $streamUrl = null;
    $mimeType = 'audio/mp4';
    $source = '';
    $upstreamHeaders = [
        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept'          => '*/*',
        'Accept-Language' => 'en-US,en;q=0.9'
    ];

    // Strategi 1: Ekstraksi lokal via bin/yt-dlp + JS runtime
    if (function_exists('exec') || function_exists('shell_exec')) {
        $ytDlpBin = getYtDlpBinary();
        $jsRuntimeFlag = getYtDlpJsRuntimeFlag();
        $safeUrl = escapeshellarg("https://www.youtube.com/watch?v=" . $videoId);
        $extractorArg = '--extractor-args ' . escapeshellarg('youtube:player_client=tv,web');

        $baseCmd = escapeshellcmd($ytDlpBin) . ' -4' . ($jsRuntimeFlag ? ' ' . $jsRuntimeFlag : '');
        $printFlags = ' -f "ba[ext=m4a]/ba/b" --no-playlist --no-warnings --print "%(url)s" --print "%(http_headers)j" --print "%(ext)s" ' . $safeUrl . ' 2>/dev/null';

        $outLines = [];
        @exec($baseCmd . ' ' . $extractorArg . $printFlags, $outLines);

        if (empty($outLines) || strpos(trim($outLines[0] ?? ''), 'http') !== 0) {
            $outLines = [];
            @exec($baseCmd . $printFlags, $outLines);
        }

        if (!empty($outLines) && strpos(trim($outLines[0] ?? ''), 'http') === 0) {
            $streamUrl = trim($outLines[0]);
            $parsedHeaders = json_decode(trim($outLines[1] ?? '{}'), true);
            if (is_array($parsedHeaders) && !empty($parsedHeaders)) {
                $upstreamHeaders = $parsedHeaders;
            }
            $ext = strtolower(trim($outLines[2] ?? 'm4a'));
            $mimeType = ($ext === 'webm' || $ext === 'opus') ? 'audio/webm' : 'audio/mp4';
            $source = 'bin/yt-dlp';
        }
    }

    // Strategi 2: Fallback multi-instance Piped API
    if (empty($streamUrl)) {
        foreach (PIPED_INSTANCES as $instance) {
            $httpRes = apolloHttpRequest(rtrim($instance, '/') . '/streams/' . $videoId, 'GET', [], null, 4);
            if ($httpRes['code'] !== 200 || empty($httpRes['body'])) continue;

            $data = json_decode($httpRes['body'], true);
            $audioStreams = $data['audioStreams'] ?? [];
            if (empty($audioStreams) || !is_array($audioStreams)) continue;

            $bestStream = null;
            foreach ($audioStreams as $as) {
                if (!empty($as['url']) && (($as['format'] ?? '') === 'M4A' || strpos($as['mimeType'] ?? '', 'mp4') !== false)) {
                    $bestStream = $as;
                    break;
                }
            }
            if (!$bestStream && !empty($audioStreams[0]['url'])) {
                $bestStream = $audioStreams[0];
            }
            if ($bestStream) {
                $streamUrl = $bestStream['url'];
                $mimeType = $bestStream['mimeType'] ?? 'audio/mp4';
                $source = 'piped:' . parse_url($instance, PHP_URL_HOST);
                break;
            }
        }
    }

    // Strategi 3: Fallback multi-instance Invidious API
    if (empty($streamUrl)) {
        foreach (INVIDIOUS_INSTANCES as $instance) {
            $httpRes = apolloHttpRequest(rtrim($instance, '/') . '/api/v1/videos/' . $videoId, 'GET', [], null, 4);
            if ($httpRes['code'] !== 200 || empty($httpRes['body'])) continue;

            $data = json_decode($httpRes['body'], true);
            foreach (($data['adaptiveFormats'] ?? []) as $fmt) {
                if (strpos($fmt['type'] ?? '', 'audio') !== false && !empty($fmt['url'])) {
                    $streamUrl = $fmt['url'];
                    $mimeType = explode(';', $fmt['type'])[0] ?: 'audio/mp4';
                    $source = 'invidious:' . parse_url($instance, PHP_URL_HOST);
                    break 2;
                }
            }
        }
    }

    if (empty($streamUrl)) {
        return null;
    }

    $resolved = [
        'video_id'   => $videoId,
        'stream_url' => $streamUrl,
        'headers'    => $upstreamHeaders,
        'mime_type'  => $mimeType,
        'source'     => $source,
        'created_at' => time()
    ];

    @file_put_contents($cacheFile, json_encode($resolved, JSON_UNESCAPED_SLASHES));
    return $resolved;
}

/**
 * Buka koneksi stream ke server upstream audio (googlevideo) dengan dukungan HTTP Range
 */
function apolloOpenUpstreamAudio($url, $hdrAssoc) {
    $lines = [];
    foreach ($hdrAssoc as $k => $v) {
        $lk = strtolower($k);
        if ($lk === 'range' || $lk === 'connection') continue;
        $lines[] = "{$k}: {$v}";
    }
    $lines[] = 'Connection: close';
    if (isset($_SERVER['HTTP_RANGE'])) {
        $lines[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
    }

    $ctx = stream_context_create([
        'http' => [
            'method'          => 'GET',
            'header'          => implode("\r\n", $lines) . "\r\n",
            'follow_location' => 1,
            'ignore_errors'   => true,
            'timeout'         => 15
        ],
        'ssl'    => ['verify_peer' => false, 'verify_peer_name' => false],
        'socket' => ['bindto' => '0.0.0.0:0']
    ]);

    $fp = @fopen($url, 'rb', false, $ctx);
    $respHeaders = [];
    if ($fp) {
        $meta = stream_get_meta_data($fp);
        if (!empty($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
            $respHeaders = $meta['wrapper_data'];
        }
    }

    $statusCode = 0;
    foreach ($respHeaders as $hdrLine) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $hdrLine, $m)) {
            $statusCode = (int)$m[1];
        }
    }

    return [$fp, $statusCode, $respHeaders];
}

/**
 * Alirkan byte audio ke browser (mendukung seek bar maju-mundur & auto-refresh jika token expired)
 */
function apolloServeProxiedAudio($videoId, $resolved) {
    apolloCloseSessionWrite();
    while (ob_get_level()) ob_end_clean();

    @set_time_limit(0);
    @ini_set('max_execution_time', '0');

    header('Access-Control-Allow-Origin: *');
    header('Accept-Ranges: bytes');

    list($fp, $statusCode, $respHeaders) = apolloOpenUpstreamAudio($resolved['stream_url'], $resolved['headers'] ?? []);

    // Jika URL cache kedaluwarsa (403/410), ambil URL baru sekali secara otomatis
    if ($statusCode === 403 || $statusCode === 410 || !$fp) {
        if ($fp) @fclose($fp);
        @unlink(apolloGetStreamCachePath($videoId));

        $fresh = apolloResolveAudioStream($videoId, true);
        if ($fresh && !empty($fresh['stream_url'])) {
            $resolved = $fresh;
            list($fp, $statusCode, $respHeaders) = apolloOpenUpstreamAudio($resolved['stream_url'], $resolved['headers'] ?? []);
        }
    }

    if (!$fp || $statusCode >= 400) {
        if ($fp) @fclose($fp);
        http_response_code(502);
        exit('Upstream stream unavailable');
    }

    $sentContentType = false;
    $contentLength = null;
    foreach ($respHeaders as $hdr) {
        $clean = trim($hdr);
        if (preg_match('/^(HTTP\/\d(?:\.\d)?\s+\d+.*|Content-Type:.*|Content-Range:.*|Content-Length:.*|Accept-Ranges:.*)$/i', $clean)) {
            header($clean);
            if (stripos($clean, 'Content-Type:') === 0) {
                $sentContentType = true;
            } elseif (preg_match('/^Content-Length:\s*(\d+)/i', $clean, $clMatch)) {
                $contentLength = (int)$clMatch[1];
            }
        }
    }
    if (!$sentContentType) {
        header('Content-Type: ' . ($resolved['mime_type'] ?? 'audio/mp4'));
    }

    $remaining = $contentLength;
    while (!feof($fp) && ($remaining === null || $remaining > 0)) {
        $readSize = ($remaining !== null) ? min(65536, $remaining) : 65536;
        $chunk = fread($fp, $readSize);
        if ($chunk === false || $chunk === '') break;
        if ($remaining !== null) {
            $remaining -= strlen($chunk);
        }
        echo $chunk;
        flush();
        if (connection_aborted()) break;
    }
    fclose($fp);
    exit;
}
