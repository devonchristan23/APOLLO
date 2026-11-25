<?php
/**
 * APOLLO — Halaman Explore / Genre Frequencies
 * Daftar 16 frekuensi genre disusun dalam array PHP agar ringkas dan mudah dibaca.
 */

$genreFrequencies = [
    ['tag' => 'FREQ 01', 'color' => '#f43f5e', 'title' => 'Pop & Global Hits',      'query' => 'Pop Global Hits',                 'sub' => 'Taylor Swift, The Weeknd, Dua Lipa',      'img' => 'assets/img/genres/indie.jpg',      'alt' => 'Pop Music'],
    ['tag' => 'FREQ 02', 'color' => '#ec4899', 'title' => 'Hip-Hop & Rap',          'query' => 'Hip Hop Rap Hits',                'sub' => 'Kendrick Lamar, Drake, Travis Scott',     'img' => 'assets/img/genres/hiphop.jpg',     'alt' => 'Hip-Hop'],
    ['tag' => 'FREQ 03', 'color' => '#a855f7', 'title' => 'R&B & Modern Soul',      'query' => 'RnB Soul Hits',                   'sub' => 'SZA, Frank Ocean, Daniel Caesar',         'img' => 'assets/img/genres/hiphop.jpg',     'alt' => 'R&B'],
    ['tag' => 'FREQ 04', 'color' => '#ef4444', 'title' => 'Rock & Classics',        'query' => 'Classic Rock Hits',               'sub' => 'Queen, Led Zeppelin, Pink Floyd',         'img' => 'assets/img/genres/nightdrive.jpg', 'alt' => 'Rock'],
    ['tag' => 'FREQ 05', 'color' => '#8b5cf6', 'title' => 'Alt & Art Rock',         'query' => 'Indie Alternative Rock',          'sub' => 'Radiohead, Arctic Monkeys, Tame Impala',  'img' => 'assets/img/genres/indie.jpg',      'alt' => 'Indie'],
    ['tag' => 'FREQ 06', 'color' => '#f59e0b', 'title' => 'Electronic / Synth',     'query' => 'Electronic Synthwave',            'sub' => 'Daft Punk, Calvin Harris, Avicii',        'img' => 'assets/img/genres/synth.jpg',      'alt' => 'Electronic'],
    ['tag' => 'FREQ 07', 'color' => '#38bdf8', 'title' => 'Ambient & Chill',        'query' => 'Ambient Chill Soundscape',        'sub' => 'Brian Eno, Tycho, M83',                   'img' => 'assets/img/genres/ambient.jpg',    'alt' => 'Ambient'],
    ['tag' => 'FREQ 08', 'color' => '#10b981', 'title' => 'Classical / Minimal',    'query' => 'Classical Minimalist Piano',      'sub' => 'Ludovico Einaudi, Hans Zimmer, Beethoven','img' => 'assets/img/genres/classical.jpg',  'alt' => 'Classical'],
    ['tag' => 'FREQ 09', 'color' => '#94a3b8', 'title' => 'Focus / Deep Work',      'query' => 'Lofi Hip Hop Chill Beats',        'sub' => 'Lofi Girl, Tape Archives, Nujabes',       'img' => 'assets/img/genres/focus.jpg',      'alt' => 'Focus'],
    ['tag' => 'FREQ 10', 'color' => '#ea580c', 'title' => 'IDM & Breakbeat',        'query' => 'IDM Breakbeat Electronic',        'sub' => 'Aphex Twin, Boards of Canada',            'img' => 'assets/img/genres/idm.jpg',        'alt' => 'IDM'],
    ['tag' => 'FREQ 11', 'color' => '#6366f1', 'title' => 'Night Commute',          'query' => 'Synthwave Retrowave Night Drive', 'sub' => 'Kavinsky, The Midnight, Chromatics',      'img' => 'assets/img/genres/nightdrive.jpg', 'alt' => 'Night Drive'],
    ['tag' => 'FREQ 12', 'color' => '#f43f5e', 'title' => 'K-Pop & Asian Wave',     'query' => 'Kpop Hits',                       'sub' => 'NewJeans, BTS, BLACKPINK',                'img' => 'assets/img/genres/synth.jpg',      'alt' => 'K-Pop'],
    ['tag' => 'FREQ 13', 'color' => '#f97316', 'title' => 'Latin & Urbano',         'query' => 'Latin Hits Reggaeton',            'sub' => 'Bad Bunny, Rosalía, J Balvin',            'img' => 'assets/img/genres/indie.jpg',      'alt' => 'Latin'],
    ['tag' => 'FREQ 14', 'color' => '#06b6d4', 'title' => 'Techno & House',         'query' => 'Deep House Melodic Techno',       'sub' => 'Peggy Gou, Tale of Us, Bicep',            'img' => 'assets/img/genres/idm.jpg',        'alt' => 'Techno'],
    ['tag' => 'FREQ 15', 'color' => '#eab308', 'title' => 'Jazz & Midnight Blues',  'query' => 'Jazz Classics',                   'sub' => 'Miles Davis, Chet Baker, Coltrane',       'img' => 'assets/img/genres/focus.jpg',      'alt' => 'Jazz'],
    ['tag' => 'FREQ 16', 'color' => '#dc2626', 'title' => 'Metal & Hard Rock',      'query' => 'Heavy Metal Rock',                'sub' => 'Metallica, Deftones, Iron Maiden',        'img' => 'assets/img/genres/nightdrive.jpg', 'alt' => 'Metal'],
];
?>
    <!-- VIEW 2: EXPLORE / GENRES VIEW -->
    <div id="view-explore">
      <div class="search-bar-hero-wrapper">
        <div class="search-bar-hero">
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
            <div class="genre-tile" onclick="filterGenre('<?= htmlspecialchars($genre['query'], ENT_QUOTES) ?>')">
              <div class="genre-tile-header">
                <span class="genre-freq-tag" style="color: <?= htmlspecialchars($genre['color']) ?>;"><?= htmlspecialchars($genre['tag']) ?></span>
                <h3 class="genre-title"><?= htmlspecialchars($genre['title']) ?></h3>
              </div>
              <span class="genre-subtitle"><?= htmlspecialchars($genre['sub']) ?></span>
              <div class="genre-img-thumb">
                <img src="<?= htmlspecialchars($genre['img']) ?>" alt="<?= htmlspecialchars($genre['alt']) ?>">
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
