<?php
/**
 * APOLLO — Helper Binary Lokal yt-dlp & Formatter Detik
 * Digunakan saat aplikasi berjalan di komputer lokal (XAMPP / PHP Built-in Server).
 */

if (!function_exists('getYtDlpBinary')) {
    function getYtDlpBinary() {
        $candidates = [
            __DIR__ . '/../bin/yt-dlp',
            '/home/king/Devon/Coding/Pelajaran/APOLLO/bin/yt-dlp',
            '/opt/lampp/htdocs/apollo/bin/yt-dlp',
            '/usr/local/bin/yt-dlp',
            '/usr/bin/yt-dlp',
            'yt-dlp'
        ];
        foreach ($candidates as $c) {
            if (file_exists($c) && is_executable($c)) {
                return $c;
            }
        }
        return 'yt-dlp';
    }
}

if (!function_exists('getYtDlpJsRuntimeFlag')) {
    /**
     * Deteksi runtime Node/Deno lokal agar yt-dlp dapat membuka signature audio YouTube
     */
    function getYtDlpJsRuntimeFlag() {
        $nodeCandidates = [
            '/home/king/.local/bin/node',
            '/usr/local/bin/node',
            '/usr/bin/node',
            '/snap/bin/node'
        ];
        foreach ($nodeCandidates as $nc) {
            if (file_exists($nc) && is_executable($nc)) {
                return '--js-runtimes ' . escapeshellarg('node:' . $nc);
            }
        }

        $denoCandidates = [
            '/home/king/.deno/bin/deno',
            '/usr/local/bin/deno',
            '/usr/bin/deno'
        ];
        foreach ($denoCandidates as $dc) {
            if (file_exists($dc) && is_executable($dc)) {
                return '--js-runtimes ' . escapeshellarg('deno:' . $dc);
            }
        }
        return '';
    }
}

if (!function_exists('formatSeconds')) {
    function formatSeconds($sec) {
        $sec = (int)$sec;
        if ($sec <= 0) return '03:30';
        $m = floor($sec / 60);
        $s = $sec % 60;
        return sprintf('%02d:%02d', $m, $s);
    }
}
