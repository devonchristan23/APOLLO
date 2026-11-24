<?php
/**
 * Apollo Minimalist Music Player - Pure Direct Audio Stream Extractor (100% PHP Native)
 * Resolves YouTube audio with JS signature runtime, caches stream metadata in /tmp,
 * and proxies byte-range audio with exact upstream headers to prevent 403 Forbidden.
 */

require_once __DIR__ . '/../config/db.php';

if (ob_get_level() == 0) ob_start();

$videoId = trim($_GET['v'] ?? '');
$isProxy = isset($_GET['proxy']) && $_GET['proxy'] == '1';
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

if (empty($videoId) || !preg_match('/^[a-zA-Z0-9_\-]{6,15}$/', $videoId)) {
    if ($isProxy) {
        http_response_code(400);
        exit('Invalid video ID');
    }
    jsonResponse(['status' => 'error', 'message' => 'Invalid or missing videoId (v)'], 400);
}

$cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/apollo_stream_v2_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $videoId) . '.json';
$cacheTtlSeconds = 2700; // 45 minutes

/**
 * Resolve stream URL + required HTTP headers for a video ID
 */
function resolveApolloAudioStream($videoId, $cacheFile, $cacheTtlSeconds, $forceRefresh = false) {
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
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept' => '*/*',
        'Accept-Language' => 'en-US,en;q=0.9'
    ];

    // =========================================================================
    // STRATEGY 1 (PRIMARY): bin/yt-dlp with JS runtime + exact http_headers
    // =========================================================================
    $ytDlpBin = getYtDlpBinary();
    $jsRuntimeFlag = getYtDlpJsRuntimeFlag();

    if (function_exists('exec') || function_exists('shell_exec')) {
        $safeUrl = escapeshellarg("https://www.youtube.com/watch?v=" . $videoId);
        $extractorArg = '--extractor-args ' . escapeshellarg('youtube:player_client=tv,web');
        $cmd = escapeshellcmd($ytDlpBin)
            . ' -4'
            . ($jsRuntimeFlag ? ' ' . $jsRuntimeFlag : '')
            . ' ' . $extractorArg
            . ' -f "ba[ext=m4a]/ba/b" --no-playlist --no-warnings'
            . ' --print "%(url)s" --print "%(http_headers)j" --print "%(ext)s" '
            . $safeUrl . ' 2>/dev/null';

        $outLines = [];
        @exec($cmd, $outLines);

        // Fallback without player_client restriction if tv,web didn't match
        if (empty($outLines) || strpos(trim($outLines[0] ?? ''), 'http') !== 0) {
            $cmdFallback = escapeshellcmd($ytDlpBin)
                . ' -4'
                . ($jsRuntimeFlag ? ' ' . $jsRuntimeFlag : '')
                . ' -f "ba[ext=m4a]/ba/b" --no-playlist --no-warnings'
                . ' --print "%(url)s" --print "%(http_headers)j" --print "%(ext)s" '
                . $safeUrl . ' 2>/dev/null';
            $outLines = [];
            @exec($cmdFallback, $outLines);
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

    // =========================================================================
    // STRATEGY 2 (FALLBACK): Multi-instance Piped API
    // =========================================================================
    if (empty($streamUrl)) {
        foreach (PIPED_INSTANCES as $instance) {
            $apiUrl = rtrim($instance, '/') . '/streams/' . $videoId;
            $httpRes = apolloHttpRequest($apiUrl, 'GET', [], null, 4);
            if ($httpRes['code'] === 200 && !empty($httpRes['body'])) {
                $data = json_decode($httpRes['body'], true);
                $audioStreams = $data['audioStreams'] ?? [];
                if (!empty($audioStreams) && is_array($audioStreams)) {
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
        }
    }

    // =========================================================================
    // STRATEGY 3 (FALLBACK): Multi-instance Invidious API
    // =========================================================================
    if (empty($streamUrl)) {
        foreach (INVIDIOUS_INSTANCES as $instance) {
            $apiUrl = rtrim($instance, '/') . '/api/v1/videos/' . $videoId;
            $httpRes = apolloHttpRequest($apiUrl, 'GET', [], null, 4);
            if ($httpRes['code'] === 200 && !empty($httpRes['body'])) {
                $data = json_decode($httpRes['body'], true);
                $formats = $data['adaptiveFormats'] ?? [];
                if (!empty($formats) && is_array($formats)) {
                    foreach ($formats as $fmt) {
                        if (strpos($fmt['type'] ?? '', 'audio') !== false && !empty($fmt['url'])) {
                            $streamUrl = $fmt['url'];
                            $mimeType = explode(';', $fmt['type'])[0] ?: 'audio/mp4';
                            $source = 'invidious:' . parse_url($instance, PHP_URL_HOST);
                            break;
                        }
                    }
                    if ($streamUrl) break;
                }
            }
        }
    }

    if (empty($streamUrl)) {
        return null;
    }

    $resolved = [
        'video_id' => $videoId,
        'stream_url' => $streamUrl,
        'headers' => $upstreamHeaders,
        'mime_type' => $mimeType,
        'source' => $source,
        'created_at' => time()
    ];

    @file_put_contents($cacheFile, json_encode($resolved, JSON_UNESCAPED_SLASHES));
    return $resolved;
}

$resolved = resolveApolloAudioStream($videoId, $cacheFile, $cacheTtlSeconds, $forceRefresh);

if (!$resolved || empty($resolved['stream_url'])) {
    if ($isProxy) {
        http_response_code(502);
        exit('Could not extract direct stream');
    }
    jsonResponse([
        'status' => 'error',
        'message' => 'Failed to resolve audio stream.'
    ], 502);
}

// =========================================================================
// PROXY STREAMING MODE (&proxy=1) - Pure PHP Native Byte-Range Audio Proxy
// =========================================================================
if ($isProxy) {
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    while (ob_get_level()) ob_end_clean();

    @set_time_limit(0);
    @ini_set('max_execution_time', '0');

    header("Access-Control-Allow-Origin: *");
    header("Accept-Ranges: bytes");

    $buildHeaderLines = function ($hdrAssoc) {
        $lines = [];
        foreach ($hdrAssoc as $k => $v) {
            $lk = strtolower($k);
            if ($lk === 'range' || $lk === 'connection') continue;
            $lines[] = "{$k}: {$v}";
        }
        $lines[] = "Connection: close";
        if (isset($_SERVER['HTTP_RANGE'])) {
            $lines[] = "Range: " . $_SERVER['HTTP_RANGE'];
        }
        return $lines;
    };

    $openUpstream = function ($url, $headerLines) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headerLines) . "\r\n",
                'follow_location' => 1,
                'ignore_errors' => true,
                'timeout' => 15
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
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
    };

    $headerLines = $buildHeaderLines($resolved['headers'] ?? []);
    list($fp, $statusCode, $respHeaders) = $openUpstream($resolved['stream_url'], $headerLines);

    // If cached googlevideo URL expired (403/410), refresh once transparently
    if ($statusCode === 403 || $statusCode === 410 || !$fp) {
        if ($fp) @fclose($fp);
        @unlink($cacheFile);
        $fresh = resolveApolloAudioStream($videoId, $cacheFile, $cacheTtlSeconds, true);
        if ($fresh && !empty($fresh['stream_url'])) {
            $resolved = $fresh;
            $headerLines = $buildHeaderLines($resolved['headers'] ?? []);
            list($fp, $statusCode, $respHeaders) = $openUpstream($resolved['stream_url'], $headerLines);
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

// =========================================================================
// METADATA JSON RESPONSE
// =========================================================================
$proxyUrl = 'api/stream.php?v=' . urlencode($videoId) . '&proxy=1';

jsonResponse([
    'status' => 'ok',
    'video_id' => $videoId,
    'stream_url' => $resolved['stream_url'],
    'proxy_url' => $proxyUrl,
    'mime_type' => $resolved['mime_type'] ?? 'audio/mp4',
    'source' => $resolved['source'] ?? 'bin/yt-dlp'
]);
