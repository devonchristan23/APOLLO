<?php
/**
 * APOLLO — Endpoint API Audio Stream & Proxy
 * - Di Vercel: langsung mengarahkan player ke Headless YT Audio Bridge (0ms, anti-blokir IP cloud)
 * - Di Lokal : mengekstrak URL stream via bin/yt-dlp dan melayani proxy audio byte-range (&proxy=1)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/stream_service.php';

if (ob_get_level() === 0) {
    ob_start();
}

$videoId      = trim($_GET['v'] ?? '');
$isProxy      = isset($_GET['proxy']) && $_GET['proxy'] === '1';
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

if ($videoId === '' || !preg_match('/^[a-zA-Z0-9_\-]{6,15}$/', $videoId)) {
    if ($isProxy) {
        http_response_code(400);
        exit('Invalid video ID');
    }
    jsonResponse(['status' => 'error', 'message' => 'Invalid or missing videoId (v)'], 400);
}

// Mode Cloud / Vercel Serverless: gunakan Headless YouTube Audio Bridge di sisi client
if (apolloIsVercelRuntime()) {
    jsonResponse([
        'status'   => 'use_yt_bridge',
        'video_id' => $videoId,
        'source'   => 'apollo_yt_bridge'
    ]);
}

// Mode Lokal (XAMPP / PHP Server): ekstrak direct stream audio
$resolved = apolloResolveAudioStream($videoId, $forceRefresh);

if (!$resolved || empty($resolved['stream_url'])) {
    if ($isProxy) {
        http_response_code(502);
        exit('Could not extract direct stream');
    }
    jsonResponse(['status' => 'error', 'message' => 'Failed to resolve audio stream.'], 502);
}

// Alirkan byte audio langsung jika dipanggil dengan &proxy=1
if ($isProxy) {
    apolloServeProxiedAudio($videoId, $resolved);
}

jsonResponse([
    'status'     => 'ok',
    'video_id'   => $videoId,
    'stream_url' => $resolved['stream_url'],
    'proxy_url'  => 'api/stream.php?v=' . urlencode($videoId) . '&proxy=1',
    'mime_type'  => $resolved['mime_type'] ?? 'audio/mp4',
    'source'     => $resolved['source'] ?? 'bin/yt-dlp'
]);
