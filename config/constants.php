<?php
/**
 * APOLLO — Konfigurasi Konstanta Eksternal
 * Menyimpan API Key opsional serta daftar instance cadangan Piped & Invidious.
 */

// Opsional: YouTube Data API v3 Key (jika diset di environment)
if (!defined('YOUTUBE_API_KEY')) {
    define('YOUTUBE_API_KEY', getenv('YOUTUBE_API_KEY') ?: '');
}

// Daftar instance publik Piped untuk cadangan pencarian & ekstraksi audio
if (!defined('PIPED_INSTANCES')) {
    define('PIPED_INSTANCES', [
        'https://api.piped.private.coffee',
        'https://pipedapi.leptons.xyz',
        'https://pipedapi.tokhmi.xyz',
        'https://piped.video/api/v1'
    ]);
}

// Daftar instance publik Invidious untuk cadangan ekstraksi audio
if (!defined('INVIDIOUS_INSTANCES')) {
    define('INVIDIOUS_INSTANCES', [
        'https://yewtu.be',
        'https://inv.nadeko.net',
        'https://invidious.nerdvpn.de',
        'https://invidious.projectsegfau.lt'
    ]);
}

/**
 * Cek apakah aplikasi sedang berjalan di environment Vercel Serverless
 */
function apolloIsVercelRuntime() {
    return (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false);
}
