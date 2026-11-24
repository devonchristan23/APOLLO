<?php
/**
 * Apollo Minimalist Music Player - Music Filter & Search Refinement Helpers
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
     * Detect local Node/Deno runtime so yt-dlp solves YouTube signatures without 403 Forbidden
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
        return sprintf("%02d:%02d", $m, $s);
    }
}

if (!function_exists('cleanMusicDetails')) {
    /**
     * Clean up title and artist from YouTube video metadata
     */
    function cleanMusicDetails($rawTitle, $uploader = '') {
        $title = $rawTitle;
        $artist = $uploader;

        // Remove - Topic from YouTube Music auto-generated channels
        $artist = preg_replace('/\s*-\s*Topic$/i', '', $artist);

        if (strpos($rawTitle, ' - ') !== false) {
            $parts = explode(' - ', $rawTitle, 2);
            $artist = trim($parts[0]);
            $title = trim($parts[1]);
        } elseif (strpos($rawTitle, ' – ') !== false) {
            $parts = explode(' – ', $rawTitle, 2);
            $artist = trim($parts[0]);
            $title = trim($parts[1]);
        } elseif (strpos($rawTitle, ': ') !== false) {
            $parts = explode(': ', $rawTitle, 2);
            $artist = trim($parts[0]);
            $title = trim($parts[1]);
        }

        // Clean brackets / tags often found in YouTube music titles
        $cleanPatterns = [
            '/\s*[\(\[](official\s*(music\s*)?video|official\s*audio|visualizer|lyric\s*video|lyrics?|remaster(ed)?(\s*\d{4})?|hd|hq|4k|audio|video|clip\s*officiel)[\)\]]/i',
            '/\s*\|\s*(official\s*(music\s*)?video|official\s*audio|visualizer|lyric\s*video|audio)\b/i',
            '/\s*-\s*(official\s*(music\s*)?video|official\s*audio)\b/i',
            '/\s*【.*?】/u',
            '/\s*「.*?」/u',
        ];
        $title = preg_replace($cleanPatterns, '', $title);
        $title = trim(trim($title, " \t\n\r\0\x0B-–|:"));

        if (empty($artist)) {
            $artist = !empty($uploader) ? preg_replace('/\s*-\s*Topic$/i', '', $uploader) : 'Unknown Artist';
        }

        return [$title, $artist];
    }
}

if (!function_exists('isLegitimateMusicTrack')) {
    /**
     * Strict validator: returns true ONLY if the item is a legitimate individual song
     */
    function isLegitimateMusicTrack($rawTitle, $durationSeconds, $uploader = '', $searchQuery = '') {
        $sec = (int)$durationSeconds;

        // 1. Duration check
        // Individual songs are typically between 45 seconds and 660 seconds (11 minutes)
        // Compilations, 1-hour loops, full albums, podcasts are > 11 mins
        // Shorts, intros, memes are < 45 seconds
        if ($sec > 0) {
            if ($sec < 45 || $sec > 660) {
                return false;
            }
        }

        $lowText = strtolower($rawTitle . ' ' . $uploader);
        $lowQuery = strtolower(trim($searchQuery));

        // 2. Strict guard against unwanted Indian / Bollywood music recommendations
        $explicitlySeekingIndian = false;
        if (!empty($lowQuery)) {
            $indianQueryTerms = [
                'hindi', 'bollywood', 'punjabi', 'bhojpuri', 'tamil', 'telugu', 
                'malayalam', 'kannada', 'urdu', 'haryanvi', 'indian', 'india', 
                'desi', 'ghazal', 'qawwali', 'bhajan', 't-series', 'tseries', 
                'arijit', 'neha kakkar', 'udit narayan', 'kumar sanu', 'alka yagnik', 
                'lata mangeshkar', 'kishore kumar', 'badshah', 'diljit', 'sidhu moose',
                'shreya ghoshal', 'sonu nigam'
            ];
            foreach ($indianQueryTerms as $term) {
                if (strpos($lowQuery, $term) !== false) {
                    $explicitlySeekingIndian = true;
                    break;
                }
            }
        }

        if (!$explicitlySeekingIndian) {
            // Block prominent Indian music record labels
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

            // Block Indian music indicators & artist names in title/uploader
            $indianPattern = '/\b(hindi(\s+old)?(\s+hits)?\s+songs?|bollywood|bhojpuri|punjabi\s+songs?|telugu\s+songs?|tamil\s+songs?|malayalam\s+songs?|kannada\s+songs?|haryanvi\s+songs?|90s\s+purane\s+gane|sadabahar\s+gaane|purane\s+gane|gana\s+video|geet\s+mp3|dj\s+remix\s+bhojpuri|bhojpuri\s+video|arijit\s+singh|udit\s+narayan|kumar\s+sanu|alka\s+yagnik|neha\s+kakkar|lata\s+mangeshkar|kishore\s+kumar|mohammed\s+rafi|sonu\s+nigam|pawan\s+singh|khesari\s+lal|sidhu\s+moose|karan\s+aujla|ap\s+dhillon|jubin\s+nautiyal|anirudh\s+ravichander|shashwat\s+sachdev|anuradha\s+paudwal)\b/i';
            if (preg_match($indianPattern, $lowText)) {
                return false;
            }
        }

        // 3. Enforce max song duration even for Topic channels
        if ($sec > 660) {
            return false;
        }

        // 4. Strict blacklist regex patterns (applied to ALL tracks including Topic/YT Music)
        $blacklist = [
            // Full albums / Compilations / Greatest Hits / Collections / Multi-song playlists / Hour loops
            '/\b(full album|complete album|greatest\s+(?:\w+\s+)?hits|best\s+(?:\w+\s+)?songs|best of\b|all songs|top \d+|discography|compilation|playlist|music collection|legendary\s+\w+|all time|non[\s\-]?stop|\d+\s*hours?|hour loop|loop \d+\s*hours?|soundtrack collection|complete ost|megamix|\d+ songs|kompilasi|kumpulan lagu)\b/i',
            
            // Reactions & commentary
            '/\b(reaction|reacts|reacting|first time hearing|vocal coach|therapist reacts|body language|breakdown|commentary)\b/i',
            
            // Interviews, podcasts, making of, documentaries
            '/\b(interview|podcast|behind the scenes|making of|documentary|docuseries|talk show|press conference|speech|audiobook|panel discussion)\b/i',
            
            // Tutorials, lessons, synthesia
            '/\b(tutorial|how to play|how to sing|how to make|piano tutorial|guitar tutorial|drum lesson|synthesia|sheet music|tabs|chords|lesson|learn to play)\b/i',
            
            // Reviews, unboxing, rankings
            '/\b(review|unboxing|analysis|explained|video essay|tier list|ranking|worth it|versus|comparison)\b/i',
            
            // Movies, trailers, gaming, memes, shorts, episodes
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
     * Normalize the user's intended search query so:
     * 1. Hyphens between title and artist (e.g. "song -artist") are not treated by YouTube as negative exclusions
     * 2. Multiple spaces are collapsed
     */
    function buildMusicSearchQuery($query) {
        $q = trim($query);
        if (empty($q)) return '';
        
        // Normalize hyphens and dashes so YouTube does not interpret "-artist" as negation/exclusion
        // e.g. "the yellow jacket -shaun martin" -> "the yellow jacket shaun martin"
        $q = preg_replace('/\s*[-–—]\s*/u', ' ', $q);
        $q = preg_replace('/\s+/', ' ', $q);
        return trim($q);
    }
}
