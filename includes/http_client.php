<?php
/**
 * APOLLO — Native PHP HTTP Client (Single & Parallel Multi-cURL)
 * Mendukung request tunggal maupun paralel agar pencarian cepat dan bebas timeout.
 */

/**
 * Header standar & User-Agent untuk request ke YouTube / YouTube Music
 */
function apolloDefaultHttpHeaders($extraHeaders = []) {
    $defaults = [
        'Accept-Language: en-US,en;q=0.9',
        'Cookie: SOCS=CAISNQgDEitib3FfaWRlbnRpdHlmcm9udGVuZHVpc2VydmVyXzIwMjMwODI5LjA3X3AxGgJlbiACGgYIgJnPpwY; CONSENT=YES+cb.20210328-17-p0.en+FX+100'
    ];
    return array_merge($defaults, $extraHeaders);
}

/**
 * HTTP Request tunggal (menggunakan cURL jika tersedia, fallback ke PHP stream context)
 */
function apolloHttpRequest($url, $method = 'GET', $headers = [], $body = null, $timeout = 5) {
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
    $mergedHeaders = apolloDefaultHttpHeaders($headers);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $mergedHeaders);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
        unset($ch);
        return ['code' => $code, 'body' => $resp !== false ? $resp : ''];
    }

    // Fallback tanpa ekstensi php-curl
    $headerLines = array_merge(['User-Agent: ' . $ua], $mergedHeaders);
    $httpOpts = [
        'method'        => strtoupper($method),
        'header'        => implode("\r\n", $headerLines) . "\r\n",
        'timeout'       => $timeout,
        'ignore_errors' => true
    ];
    if ($body !== null) {
        $httpOpts['content'] = $body;
    }

    $ctx = stream_context_create([
        'http'   => $httpOpts,
        'ssl'    => ['verify_peer' => false, 'verify_peer_name' => false],
        'socket' => ['bindto' => '0.0.0.0:0']
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    $respHeaders = function_exists('http_get_last_response_headers')
        ? (http_get_last_response_headers() ?: [])
        : ($http_response_header ?? []);

    $code = 0;
    foreach ($respHeaders as $hdrLine) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $hdrLine, $m)) {
            $code = (int)$m[1];
        }
    }
    return ['code' => $code, 'body' => $resp !== false ? $resp : ''];
}

/**
 * Eksekusi banyak HTTP Request sekaligus secara paralel via curl_multi
 */
function apolloHttpParallel($requests, $timeout = 4) {
    if (!function_exists('curl_multi_init')) {
        $results = [];
        foreach ($requests as $key => $req) {
            $results[$key] = apolloHttpRequest(
                $req['url'],
                $req['method'] ?? 'GET',
                $req['headers'] ?? [],
                $req['body'] ?? null,
                $req['timeout'] ?? $timeout
            );
        }
        return $results;
    }

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
    $mh = curl_multi_init();
    $handles = [];

    foreach ($requests as $key => $req) {
        $ch = curl_init($req['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $req['timeout'] ?? $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);

        if (strtoupper($req['method'] ?? 'GET') === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (isset($req['body']) && $req['body'] !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $req['body']);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, apolloDefaultHttpHeaders($req['headers'] ?? []));
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
    } while ($status === CURLM_CALL_MULTI_PERFORM);

    while ($running > 0 && $status === CURLM_OK) {
        if (curl_multi_select($mh, 0.2) === -1) {
            usleep(10000);
        }
        do {
            $status = curl_multi_exec($mh, $running);
        } while ($status === CURLM_CALL_MULTI_PERFORM);
    }

    $results = [];
    foreach ($handles as $key => $ch) {
        $body = curl_multi_getcontent($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $results[$key] = ['code' => $code, 'body' => $body !== false ? $body : ''];
        curl_multi_remove_handle($mh, $ch);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
        unset($ch);
    }
    curl_multi_close($mh);

    return $results;
}
