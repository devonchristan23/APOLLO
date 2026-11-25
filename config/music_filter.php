<?php
/**
 * APOLLO — Filter Lagu Valid & Pembersih Metadata Musik
 * Menyaring video non-lagu (kompilasi, full album, podcast, reaction, tutorial)
 * serta merapikan judul dan nama artis.
 */

require_once __DIR__ . '/../includes/ytdlp_helper.php';

if (!function_exists('cleanMusicDetails')) {
    /**
     * Bersihkan judul lagu & nama artis dari embel-embel judul video YouTube
     */
    function cleanMusicDetails($rawTitle, $uploader = '') {
        $title = $rawTitle;
        $artist = preg_replace('/\s*-\s*Topic$/i', '', $uploader);

        if (strpos($rawTitle, ' - ') !== false) {
            list($artist, $title) = array_map('trim', explode(' - ', $rawTitle, 2));
        } elseif (strpos($rawTitle, ' – ') !== false) {
            list($artist, $title) = array_map('trim', explode(' – ', $rawTitle, 2));
        } elseif (strpos($rawTitle, ': ') !== false) {
            list($artist, $title) = array_map('trim', explode(': ', $rawTitle, 2));
        }

        $cleanPatterns = [
            '/\s*[\(\[](official\s*(music\s*)?video|official\s*audio|visualizer|lyric\s*video|lyrics?|remaster(ed)?(\s*\d{4})?|hd|hq|4k|audio|video|clip\s*officiel)[\)\]]/i',
            '/\s*\|\s*(official\s*(music\s*)?video|official\s*audio|visualizer|lyric\s*video|audio)\b/i',
            '/\s*-\s*(official\s*(music\s*)?video|official\s*audio)\b/i',
            '/\s*【.*?】/u',
            '/\s*「.*?」/u',
        ];
        $title = preg_replace($cleanPatterns, '', $title);
        $title = trim(trim($title, " \t\n\r\0\x0B-–|:"));

        if ($artist === '') {
            $artist = !empty($uploader) ? preg_replace('/\s*-\s*Topic$/i', '', $uploader) : 'Unknown Artist';
        }

        return [$title, $artist];
    }
}

if (!function_exists('isLegitimateMusicTrack')) {
    /**
     * Validasi ketat: bernilai true HANYA jika item adalah lagu tunggal yang valid
     */
    function isLegitimateMusicTrack($rawTitle, $durationSeconds, $uploader = '', $searchQuery = '') {
        $sec = (int)$durationSeconds;

        // 1. Batas durasi lagu wajar (45 detik s/d 11 menit)
        if ($sec > 0 && ($sec < 45 || $sec > 660)) {
            return false;
        }

        $lowText = strtolower($rawTitle . ' ' . $uploader);
        $lowQuery = strtolower(trim($searchQuery));

        // 2. Saring rekomendasi katalog regional India kecuali memang sedang dicari user
        $seekingIndian = false;
        if ($lowQuery !== '') {
            $indianTerms = [
                'hindi', 'bollywood', 'punjabi', 'bhojpuri', 'tamil', 'telugu',
                'malayalam', 'kannada', 'urdu', 'haryanvi', 'indian', 'india',
                'desi', 'ghazal', 'qawwali', 'bhajan', 't-series', 'tseries',
                'arijit', 'neha kakkar', 'udit narayan', 'kumar sanu', 'alka yagnik',
                'lata mangeshkar', 'kishore kumar', 'badshah', 'diljit', 'sidhu moose',
                'shreya ghoshal', 'sonu nigam'
            ];
            foreach ($indianTerms as $term) {
                if (strpos($lowQuery, $term) !== false) {
                    $seekingIndian = true;
                    break;
                }
            }
        }

        if (!$seekingIndian) {
            $indianLabels = [
                't-series', 'tseries', 'zee music', 'sony music india', 'sonymusicindia',
                'tips official', 'tips music', 'tips industries', 'saregama', 'yrf',
                'speed records', 'aditya music', 'lahari music', 'wave music',
                'venus worldwide', 'ishtar music', 'eros now', 'shemaroo', 'times music',
                'desi music factory', 'white hill music', 'geet mp3', 'single track studios',
                'dimple chauhan', 'hit hindi songs', 'bollywood dhamaka'
            ];
            foreach ($indianLabels as $label) {
                if (strpos($lowText, $label) !== false) {
                    return false;
                }
            }

            $indianPattern = '/\b(hindi(\s+old)?(\s+hits)?\s+songs?|bollywood|bhojpuri|punjabi\s+songs?|telugu\s+songs?|tamil\s+songs?|malayalam\s+songs?|kannada\s+songs?|haryanvi\s+songs?|90s\s+purane\s+gane|sadabahar\s+gaane|purane\s+gane|gana\s+video|geet\s+mp3|dj\s+remix\s+bhojpuri|bhojpuri\s+video|arijit\s+singh|udit\s+narayan|kumar\s+sanu|alka\s+yagnik|neha\s+kakkar|lata\s+mangeshkar|kishore\s+kumar|mohammed\s+rafi|sonu\s+nigam|pawan\s+singh|khesari\s+lal|sidhu\s+moose|karan\s+aujla|ap\s+dhillon|jubin\s+nautiyal|anirudh\s+ravichander|shashwat\s+sachdev|anuradha\s+paudwal|kailash\s+kher|shaan|chand\s+sifarish|fanaa|yash\s+raj|pritam|vishal[\s\-]shekhar|atif\s+aslam|rahat\s+fateh|shankar\s+ehsaan)\b/i';
            if (preg_match($indianPattern, $lowText)) {
                return false;
            }
        }

        // 3. Blacklist video non-lagu tunggal (kompilasi, greatest hits, podcast, reaction, tutorial, dll.)
        $blacklist = [
            '/\b(full album|complete album|greatest\s+(?:\w+\s+)?hits|best\s+(?:\w+\s+)?songs|best of\b|all songs|top \d+|discography|compilation|playlist|music collection|legendary\s+\w+|all time|non[\s\-]?stop|\d+\s*hours?|hour loop|loop \d+\s*hours?|soundtrack collection|complete ost|megamix|\d+ songs|kompilasi|kumpulan lagu)\b/i',
            '/\b(reaction|reacts|reacting|first time hearing|vocal coach|therapist reacts|body language|breakdown|commentary)\b/i',
            '/\b(interview|podcast|behind the scenes|making of|documentary|docuseries|talk show|press conference|speech|audiobook|panel discussion)\b/i',
            '/\b(tutorial|how to play|how to sing|how to make|piano tutorial|guitar tutorial|drum lesson|synthesia|sheet music|tabs|chords|lesson|learn to play)\b/i',
            '/\b(review|unboxing|analysis|explained|video essay|tier list|ranking|worth it|versus|comparison)\b/i',
            '/\b(trailer|teaser|sneak peek|movie clip|film scene|cinematic|full movie|tv series|tv show|recap)\b/i',
            '/\b(gameplay|walkthrough|playthrough|let\'?s play|speedrun|mod showcase|funny moments|parody|meme|tiktok compilation|shorts|vlog|prank)\b/i',
            '/\b(episode\s*\d+|season\s*\d+|ep\.\s*\d+|part\s*\d+\s*of\s*\d+)\b/i',
        ];

        foreach ($blacklist as $pattern) {
            if (preg_match($pattern, $lowText)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('buildMusicSearchQuery')) {
    /**
     * Rapikan tanda hubung pada kata kunci agar tidak dianggap operator minus oleh YouTube
     */
    function buildMusicSearchQuery($query) {
        $q = trim($query);
        if ($q === '') return '';
        $q = preg_replace('/\s*[-–—]\s*/u', ' ', $q);
        $q = preg_replace('/\s+/', ' ', $q);
        return trim($q);
    }
}
