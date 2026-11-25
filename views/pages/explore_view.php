<?php
/**
 * APOLLO — Halaman Explore / Genre Frequencies
 * 16 frekuensi genre dengan grafik vektor industrial bergaya Braun Dieter Rams (tanpa foto kaku).
 */

$genreFrequencies = [
    ['tag' => 'FREQ 01', 'color' => '#f43f5e', 'title' => 'Pop & Global Hits',     'query' => 'Pop Global Hits',                 'sub' => 'Taylor Swift, The Weeknd, Dua Lipa',       'graphic' => 'bars'],
    ['tag' => 'FREQ 02', 'color' => '#ec4899', 'title' => 'Hip-Hop & Rap',         'query' => 'Hip Hop Rap Hits',                'sub' => 'Kendrick Lamar, Drake, Travis Scott',      'graphic' => 'pulse'],
    ['tag' => 'FREQ 03', 'color' => '#a855f7', 'title' => 'R&B & Modern Soul',     'query' => 'RnB Soul Hits',                   'sub' => 'SZA, Frank Ocean, Daniel Caesar',          'graphic' => 'sine'],
    ['tag' => 'FREQ 04', 'color' => '#ef4444', 'title' => 'Rock & Classics',       'query' => 'Classic Rock Hits',               'sub' => 'Queen, Led Zeppelin, Pink Floyd',          'graphic' => 'jagged'],
    ['tag' => 'FREQ 05', 'color' => '#8b5cf6', 'title' => 'Alt & Art Rock',        'query' => 'Indie Alternative Rock',          'sub' => 'Radiohead, Arctic Monkeys, Tame Impala',   'graphic' => 'vinyl'],
    ['tag' => 'FREQ 06', 'color' => '#f59e0b', 'title' => 'Electronic / Synth',    'query' => 'Electronic Synthwave',            'sub' => 'Daft Punk, Calvin Harris, Avicii',         'graphic' => 'square'],
    ['tag' => 'FREQ 07', 'color' => '#38bdf8', 'title' => 'Ambient & Chill',       'query' => 'Ambient Chill Soundscape',        'sub' => 'Brian Eno, Tycho, M83',                    'graphic' => 'ripples'],
    ['tag' => 'FREQ 08', 'color' => '#10b981', 'title' => 'Classical / Minimal',   'query' => 'Classical Minimalist Piano',      'sub' => 'Ludovico Einaudi, Hans Zimmer, Beethoven', 'graphic' => 'arcs'],
    ['tag' => 'FREQ 09', 'color' => '#94a3b8', 'title' => 'Focus / Deep Work',     'query' => 'Lofi Hip Hop Chill Beats',        'sub' => 'Lofi Girl, Tape Archives, Nujabes',        'graphic' => 'tape'],
    ['tag' => 'FREQ 10', 'color' => '#ea580c', 'title' => 'IDM & Breakbeat',       'query' => 'IDM Breakbeat Electronic',        'sub' => 'Aphex Twin, Boards of Canada',             'graphic' => 'glitch'],
    ['tag' => 'FREQ 11', 'color' => '#6366f1', 'title' => 'Night Commute',         'query' => 'Synthwave Retrowave Night Drive', 'sub' => 'Kavinsky, The Midnight, Chromatics',       'graphic' => 'horizon'],
    ['tag' => 'FREQ 12', 'color' => '#f43f5e', 'title' => 'K-Pop & Asian Wave',    'query' => 'Kpop Hits',                       'sub' => 'NewJeans, BTS, BLACKPINK',                 'graphic' => 'prism'],
    ['tag' => 'FREQ 13', 'color' => '#f97316', 'title' => 'Latin & Urbano',        'query' => 'Latin Hits Reggaeton',            'sub' => 'Bad Bunny, Rosalía, J Balvin',             'graphic' => 'rhythm'],
    ['tag' => 'FREQ 14', 'color' => '#06b6d4', 'title' => 'Techno & House',        'query' => 'Deep House Melodic Techno',       'sub' => 'Peggy Gou, Tale of Us, Bicep',             'graphic' => 'matrix'],
    ['tag' => 'FREQ 15', 'color' => '#eab308', 'title' => 'Jazz & Midnight Blues', 'query' => 'Jazz Classics',                   'sub' => 'Miles Davis, Chet Baker, Coltrane',        'graphic' => 'dial'],
    ['tag' => 'FREQ 16', 'color' => '#dc2626', 'title' => 'Metal & Hard Rock',     'query' => 'Heavy Metal Rock',                'sub' => 'Metallica, Deftones, Iron Maiden',         'graphic' => 'spikes'],
];

/**
 * Render grafik SVG industrial bertema APOLLO sesuai karakter masing-masing genre
 */
function apolloRenderGenreGraphic($type, $color) {
    $c = htmlspecialchars($color, ENT_QUOTES);
    $grid = '<g stroke="#2a2a2a" stroke-width="0.75"><line x1="0" y1="24" x2="96" y2="24"/><line x1="0" y1="48" x2="96" y2="48"/><line x1="0" y1="72" x2="96" y2="72"/><line x1="32" y1="0" x2="32" y2="96"/><line x1="64" y1="0" x2="64" y2="96"/></g>';

    switch ($type) {
        case 'bars':
            $art = "<rect x='10' y='52' width='8' height='34' fill='#2a2a2a'/><rect x='24' y='34' width='8' height='52' fill='{$c}' fill-opacity='0.7'/><rect x='38' y='18' width='8' height='68' fill='{$c}'/><rect x='52' y='40' width='8' height='46' fill='#FF4F00'/><rect x='66' y='26' width='8' height='60' fill='{$c}' fill-opacity='0.8'/><rect x='80' y='58' width='8' height='28' fill='#383838'/>";
            break;
        case 'pulse':
            $art = "<polyline points='4,60 18,60 26,22 34,78 44,44 52,60 64,60 72,18 80,74 88,60 94,60' fill='none' stroke='{$c}' stroke-width='2.4' stroke-linejoin='miter'/><circle cx='72' cy='18' r='3.5' fill='#FF4F00'/>";
            break;
        case 'sine':
            $art = "<path d='M0 54 Q24 16 48 54 T96 54' fill='none' stroke='{$c}' stroke-width='2.4'/><path d='M0 64 Q24 26 48 64 T96 64' fill='none' stroke='#FF4F00' stroke-width='1.5' stroke-opacity='0.75'/><path d='M0 44 Q24 6 48 44 T96 44' fill='none' stroke='#525252' stroke-width='1.2'/>";
            break;
        case 'jagged':
            $art = "<polygon points='8,82 24,28 38,62 52,16 68,58 80,32 90,82' fill='{$c}' fill-opacity='0.14' stroke='{$c}' stroke-width='2.2'/><circle cx='52' cy='16' r='3.5' fill='#FF4F00'/>";
            break;
        case 'vinyl':
            $art = "<circle cx='56' cy='56' r='38' fill='none' stroke='#3a3a3a' stroke-width='1.4'/><circle cx='56' cy='56' r='28' fill='none' stroke='{$c}' stroke-width='1.6' stroke-dasharray='14 5'/><circle cx='56' cy='56' r='18' fill='none' stroke='#555' stroke-width='1.2'/><circle cx='56' cy='56' r='7' fill='#FF4F00'/><line x1='16' y1='20' x2='48' y2='48' stroke='#F4F4F0' stroke-width='1.6'/>";
            break;
        case 'square':
            $art = "<polyline points='4,72 18,72 18,32 34,32 34,64 50,64 50,20 68,20 68,72 84,72 84,42 94,42' fill='none' stroke='{$c}' stroke-width='2.4'/><rect x='46' y='16' width='8' height='8' fill='#FF4F00'/>";
            break;
        case 'ripples':
            $art = "<circle cx='54' cy='54' r='10' fill='{$c}' fill-opacity='0.25' stroke='{$c}' stroke-width='1.8'/><circle cx='54' cy='54' r='22' fill='none' stroke='{$c}' stroke-width='1.4' stroke-opacity='0.7'/><circle cx='54' cy='54' r='34' fill='none' stroke='#525252' stroke-width='1.2'/><circle cx='54' cy='54' r='3' fill='#FF4F00'/>";
            break;
        case 'arcs':
            $art = "<path d='M12 84 A40 40 0 0 1 88 84' fill='none' stroke='{$c}' stroke-width='2.2'/><path d='M24 84 A28 28 0 0 1 76 84' fill='none' stroke='#737373' stroke-width='1.6'/><path d='M36 84 A16 16 0 0 1 64 84' fill='none' stroke='#FF4F00' stroke-width='2'/><line x1='50' y1='84' x2='50' y2='24' stroke='#404040' stroke-width='1.2' stroke-dasharray='3 3'/>";
            break;
        case 'tape':
            $art = "<rect x='10' y='26' width='76' height='48' fill='#141414' stroke='#444' stroke-width='1.5'/><circle cx='32' cy='50' r='13' fill='none' stroke='{$c}' stroke-width='2'/><circle cx='64' cy='50' r='13' fill='none' stroke='{$c}' stroke-width='2'/><circle cx='32' cy='50' r='4' fill='#FF4F00'/><circle cx='64' cy='50' r='4' fill='#F4F4F0'/><line x1='32' y1='37' x2='64' y2='37' stroke='#555' stroke-width='1.2'/>";
            break;
        case 'glitch':
            $art = "<rect x='12' y='20' width='22' height='10' fill='{$c}'/><rect x='40' y='20' width='42' height='10' fill='#2a2a2a'/><rect x='22' y='38' width='36' height='10' fill='#FF4F00'/><rect x='64' y='38' width='18' height='10' fill='{$c}' fill-opacity='0.6'/><rect x='10' y='56' width='50' height='10' fill='#333'/><rect x='66' y='56' width='20' height='10' fill='{$c}'/><rect x='30' y='74' width='46' height='8' fill='{$c}' fill-opacity='0.45'/>";
            break;
        case 'horizon':
            $art = "<circle cx='52' cy='46' r='22' fill='{$c}' fill-opacity='0.22' stroke='{$c}' stroke-width='1.8'/><line x1='6' y1='56' x2='90' y2='56' stroke='#FF4F00' stroke-width='2'/><line x1='16' y1='66' x2='80' y2='66' stroke='{$c}' stroke-width='1.4'/><line x1='26' y1='76' x2='70' y2='76' stroke='#525252' stroke-width='1.2'/><line x1='38' y1='86' x2='58' y2='86' stroke='#404040' stroke-width='1'/>";
            break;
        case 'prism':
            $art = "<polygon points='50,14 84,78 16,78' fill='{$c}' fill-opacity='0.14' stroke='{$c}' stroke-width='2'/><polygon points='50,32 70,70 30,70' fill='none' stroke='#FF4F00' stroke-width='1.6'/><circle cx='50' cy='56' r='4' fill='#F4F4F0'/>";
            break;
        case 'rhythm':
            $art = "<circle cx='22' cy='34' r='8' fill='{$c}'/><circle cx='50' cy='34' r='5' fill='#383838'/><circle cx='76' cy='34' r='10' fill='none' stroke='#FF4F00' stroke-width='2.2'/><circle cx='22' cy='68' r='5' fill='#383838'/><circle cx='50' cy='68' r='11' fill='{$c}' fill-opacity='0.3' stroke='{$c}' stroke-width='2'/><circle cx='76' cy='68' r='6' fill='#F4F4F0'/>";
            break;
        case 'matrix':
            $art = "<g fill='#262626'><rect x='14' y='16' width='14' height='14'/><rect x='34' y='16' width='14' height='14' fill='{$c}'/><rect x='54' y='16' width='14' height='14'/><rect x='74' y='16' width='14' height='14' fill='{$c}' fill-opacity='0.5'/><rect x='14' y='36' width='14' height='14' fill='{$c}' fill-opacity='0.7'/><rect x='34' y='36' width='14' height='14'/><rect x='54' y='36' width='14' height='14' fill='#FF4F00'/><rect x='74' y='36' width='14' height='14'/><rect x='14' y='56' width='14' height='14'/><rect x='34' y='56' width='14' height='14' fill='{$c}'/><rect x='54' y='56' width='14' height='14' fill='{$c}' fill-opacity='0.5'/><rect x='74' y='56' width='14' height='14' fill='#FF4F00'/></g>";
            break;
        case 'dial':
            $art = "<path d='M16 74 A36 36 0 1 1 84 74' fill='none' stroke='#404040' stroke-width='3'/><path d='M16 74 A36 36 0 0 1 68 24' fill='none' stroke='{$c}' stroke-width='3'/><line x1='50' y1='64' x2='72' y2='30' stroke='#FF4F00' stroke-width='2.4'/><circle cx='50' cy='64' r='6' fill='#181818' stroke='#F4F4F0' stroke-width='2'/>";
            break;
        default: // spikes
            $art = "<polyline points='4,78 14,22 24,78 34,12 44,78 54,28 64,78 74,10 84,78 94,40' fill='none' stroke='{$c}' stroke-width='2.4' stroke-linejoin='miter'/><line x1='4' y1='46' x2='94' y2='46' stroke='#FF4F00' stroke-width='1.2' stroke-dasharray='4 3'/>";
            break;
    }

    return '<svg class="genre-svg-graphic" viewBox="0 0 96 96" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' . $grid . $art . '</svg>';
}
?>
    <!-- VIEW 2: EXPLORE / GENRES VIEW -->
    <div id="view-explore">
      <div class="search-bar-hero-wrapper">
        <div class="search-bar-row">
          <div class="search-bar-hero" style="flex: 1;">
            <span class="material-symbols-outlined search-icon">search</span>
            <input
              type="text"
              id="explore-search-input"
              class="search-input"
              placeholder="Search global music signals, genres, artists, master albums..."
              autocomplete="off"
              spellcheck="false"
            >
            <span class="search-esc-hint">[ESC] CLEAR</span>
          </div>
          <button type="button" class="btn-search-refresh btn-hero-refresh" id="btn-refresh-explore" title="Refresh Explore">
            <span class="material-symbols-outlined" style="font-size: 22px;">refresh</span>
          </button>
        </div>
      </div>

      <section>
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h2 class="section-title">EXPLORE · GLOBAL GENRE FREQUENCIES</h2>
          </div>
          <span class="section-meta">MODULE: MATRIX_GRID [<?= count($genreFrequencies) ?> FREQUENCIES]</span>
        </div>

        <div class="genres-grid">
          <?php foreach ($genreFrequencies as $genre): ?>
            <div class="genre-tile" onclick="filterGenre('<?= htmlspecialchars($genre['query'], ENT_QUOTES) ?>')" style="--genre-accent: <?= htmlspecialchars($genre['color']) ?>;">
              <div class="genre-tile-header">
                <span class="genre-freq-tag" style="color: <?= htmlspecialchars($genre['color']) ?>;"><?= htmlspecialchars($genre['tag']) ?></span>
                <h3 class="genre-title"><?= htmlspecialchars($genre['title']) ?></h3>
              </div>
              <span class="genre-subtitle"><?= htmlspecialchars($genre['sub']) ?></span>
              <div class="genre-graphic-wrap">
                <?= apolloRenderGenreGraphic($genre['graphic'], $genre['color']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
