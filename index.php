<?php
/**
 * APOLLO Minimalist Music Player
 * Swiss Grid Typography & Industrial Braun Dieter Rams Audio Aesthetic
 * Compatible with XAMPP Ubuntu (/opt/lampp/htdocs/apollo) & Vercel Serverless
 */

require_once __DIR__ . '/config/db.php';

// Automatically record every visitor opening APOLLO in MySQL (visitor_logs)
apolloTrackVisitor($_SERVER['REQUEST_URI'] ?? '/');
$initialUser = apolloGetCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>APOLLO — Minimalist Music Player</title>
  <meta name="description" content="Minimalist industrial audio streaming player inspired by Braun Dieter Rams aesthetic and Swiss Grid Typography.">
  <meta name="theme-color" content="#111111">

  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Material Symbols Outlined -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

  <!-- Apollo Stylesheet with Cache-Buster -->
  <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body>

<div id="app-container">

  <!-- ==================== LEFT SIDEBAR (DESKTOP) ==================== -->
  <aside id="sidebar">
    <div class="brand-container">
      <span class="brand-title">APOLLO</span>
    </div>

    <div class="sidebar-scroll">
      <!-- Main Navigation Links -->
      <nav class="nav-group">
        <a href="#" class="nav-item active" data-nav="home">
          <span class="material-symbols-outlined">home</span>
          <span>Home</span>
        </a>
        <a href="#" class="nav-item" data-nav="explore">
          <span class="material-symbols-outlined">explore</span>
          <span>Explore / Genres</span>
        </a>
        <a href="#" class="nav-item" data-nav="library">
          <span class="material-symbols-outlined">library_music</span>
          <span>Library</span>
        </a>
      </nav>

      <!-- User Playlists Section -->
      <div class="playlists-section">
        <div class="section-label-row">
          <span class="chassis-label">PLAYLISTS</span>
        </div>

        <nav class="playlist-nav-list" id="sidebar-playlists-list">
          <!-- Dynamically populated via player.js from MySQL / LocalStorage -->
        </nav>

        <button type="button" class="btn-new-playlist" id="btn-new-playlist">
          + NEW PLAYLIST
        </button>
      </div>
    </div>
  </aside>

  <!-- ==================== MAIN VIEW CONTAINER ==================== -->
  <main id="main-view">
    <!-- Top Header Bar with User Account Controls -->
    <header id="top-header">
      <div class="engine-status">
        <span class="signal-dot"></span>
        <span>AUDIO ENGINE: ACTIVE [44.1kHz / 24-BIT DIETER RAMS MATRIX]</span>
      </div>
      <div class="header-right-group">
        <div class="engine-badge hide-on-sm">
          <span>HI-RES PCM · ZERO ADS</span>
        </div>

        <!-- Guest State -->
        <div id="auth-guest-box" style="<?= $initialUser ? 'display: none;' : 'display: inline-flex;' ?> align-items: center; gap: 8px;">
          <button type="button" class="btn-header-auth" id="btn-open-auth">
            <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
            <span>LOGIN / REGISTER</span>
          </button>
        </div>

        <!-- Authenticated User State -->
        <div id="auth-user-box" style="<?= $initialUser ? 'display: inline-flex;' : 'display: none;' ?> align-items: center; gap: 8px;">
          <div class="user-session-badge">
            <span class="signal-dot"></span>
            <span id="header-username"><?= $initialUser ? htmlspecialchars($initialUser['username']) : 'USER' ?></span>
          </div>
          <button type="button" class="btn-header-logout" id="btn-logout" title="Sign out">
            LOGOUT
          </button>
        </div>
      </div>
    </header>

    <!-- VIEW 1: HOME / OVERVIEW STATE (Pure Listening History & Recommendations) -->
    <div id="view-overview">
      <!-- Search Bar on Home -->
      <div class="search-bar-wrapper">
        <div class="search-bar">
          <span class="material-symbols-outlined search-icon">search</span>
          <input 
            type="text" 
            id="global-search-input" 
            class="search-input" 
            placeholder="Search songs, artists..." 
            autocomplete="off"
            spellcheck="false"
          >
          <span class="search-esc-hint">[ESC] CLEAR</span>
        </div>
      </div>

      <!-- Section: Live YouTube Music Recommended Albums -->
      <section id="overview-albums-section">
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h2 class="section-title" id="overview-albums-title">RECOMMENDED FROM YOUTUBE MUSIC</h2>
          </div>
          <span class="section-meta" id="overview-albums-meta">LIVE YOUTUBE MUSIC FEED</span>
        </div>

        <!-- 4-Column Grid of YouTube Music Recommended Cards -->
        <div class="albums-grid" id="albums-grid">
          <!-- Populated dynamically via player.js from YouTube Music -->
        </div>
      </section>

      <!-- Section: Recently Played / Search Results Tracks -->
      <section>
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot" style="background-color: var(--border-line-light);"></span>
            <h2 class="section-title" id="tracks-section-title">RECENTLY PLAYED / REFERENCE TRACKS</h2>
          </div>
          <span class="section-meta" id="tracks-meta-count">10 TRACKS</span>
        </div>

        <!-- Render Track Table -->
        <div class="tracks-table-wrap">
          <table class="tracks-table">
            <thead>
              <tr>
                <th class="col-num">#</th>
                <th class="col-title">TITLE</th>
                <th class="col-artist">ARTIST</th>
                <th class="col-dur">DURATION</th>
                <th class="col-action">ACTION</th>
              </tr>
            </thead>
            <tbody id="tracks-table-body">
              <!-- Dynamically populated via player.js -->
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- VIEW 2: EXPLORE / GENRES VIEW (Large Centered Search Bar & Rich Global Frequencies) -->
    <div id="view-explore">
      <!-- Enlarged Centered Search Bar -->
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

      <!-- Section: Global Genres & Mood Frequencies -->
      <section>
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h2 class="section-title">EXPLORE · GLOBAL GENRE FREQUENCIES</h2>
          </div>
          <span class="section-meta">MODULE: MATRIX_GRID [16 FREQUENCIES]</span>
        </div>

        <!-- 16 Globally Popular Genre Tiles -->
        <div class="genres-grid">
          <!-- 01. Pop & Global Hits -->
          <div class="genre-tile" onclick="filterGenre('Pop Global Hits')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #f43f5e;">FREQ 01</span>
              <h3 class="genre-title">Pop &amp; Global Hits</h3>
            </div>
            <span class="genre-subtitle">Taylor Swift, The Weeknd, Dua Lipa</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/indie.jpg" alt="Pop Music">
            </div>
          </div>

          <!-- 02. Hip-Hop & Rap -->
          <div class="genre-tile" onclick="filterGenre('Hip Hop Rap Hits')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #ec4899;">FREQ 02</span>
              <h3 class="genre-title">Hip-Hop &amp; Rap</h3>
            </div>
            <span class="genre-subtitle">Kendrick Lamar, Drake, Travis Scott</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/hiphop.jpg" alt="Hip-Hop">
            </div>
          </div>

          <!-- 03. R&B & Soul -->
          <div class="genre-tile" onclick="filterGenre('RnB Soul Hits')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #a855f7;">FREQ 03</span>
              <h3 class="genre-title">R&amp;B &amp; Modern Soul</h3>
            </div>
            <span class="genre-subtitle">SZA, Frank Ocean, Daniel Caesar</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/hiphop.jpg" alt="R&B">
            </div>
          </div>

          <!-- 04. Rock & Classic -->
          <div class="genre-tile" onclick="filterGenre('Classic Rock Hits')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #ef4444;">FREQ 04</span>
              <h3 class="genre-title">Rock &amp; Classics</h3>
            </div>
            <span class="genre-subtitle">Queen, Led Zeppelin, Pink Floyd</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/nightdrive.jpg" alt="Rock">
            </div>
          </div>

          <!-- 05. Alternative & Indie -->
          <div class="genre-tile" onclick="filterGenre('Indie Alternative Rock')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #8b5cf6;">FREQ 05</span>
              <h3 class="genre-title">Alt &amp; Art Rock</h3>
            </div>
            <span class="genre-subtitle">Radiohead, Arctic Monkeys, Tame Impala</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/indie.jpg" alt="Indie">
            </div>
          </div>

          <!-- 06. Electronic & Synth -->
          <div class="genre-tile" onclick="filterGenre('Electronic Synthwave')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #f59e0b;">FREQ 06</span>
              <h3 class="genre-title">Electronic / Synth</h3>
            </div>
            <span class="genre-subtitle">Daft Punk, Calvin Harris, Avicii</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/synth.jpg" alt="Electronic">
            </div>
          </div>

          <!-- 07. Ambient & Chill -->
          <div class="genre-tile" onclick="filterGenre('Ambient Chill Soundscape')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #38bdf8;">FREQ 07</span>
              <h3 class="genre-title">Ambient &amp; Chill</h3>
            </div>
            <span class="genre-subtitle">Brian Eno, Tycho, M83</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/ambient.jpg" alt="Ambient">
            </div>
          </div>

          <!-- 08. Classical & Minimalist -->
          <div class="genre-tile" onclick="filterGenre('Classical Minimalist Piano')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #10b981;">FREQ 08</span>
              <h3 class="genre-title">Classical / Minimal</h3>
            </div>
            <span class="genre-subtitle">Ludovico Einaudi, Hans Zimmer, Beethoven</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/classical.jpg" alt="Classical">
            </div>
          </div>

          <!-- 09. Deep Focus & Study -->
          <div class="genre-tile" onclick="filterGenre('Lofi Hip Hop Chill Beats')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #94a3b8;">FREQ 09</span>
              <h3 class="genre-title">Focus / Deep Work</h3>
            </div>
            <span class="genre-subtitle">Lofi Girl, Tape Archives, Nujabes</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/focus.jpg" alt="Focus">
            </div>
          </div>

          <!-- 10. IDM & Glitch -->
          <div class="genre-tile" onclick="filterGenre('IDM Breakbeat Electronic')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #ea580c;">FREQ 10</span>
              <h3 class="genre-title">IDM &amp; Breakbeat</h3>
            </div>
            <span class="genre-subtitle">Aphex Twin, Boards of Canada</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/idm.jpg" alt="IDM">
            </div>
          </div>

          <!-- 11. Night Drive / Synthwave -->
          <div class="genre-tile" onclick="filterGenre('Synthwave Retrowave Night Drive')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #6366f1;">FREQ 11</span>
              <h3 class="genre-title">Night Commute</h3>
            </div>
            <span class="genre-subtitle">Kavinsky, The Midnight, Chromatics</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/nightdrive.jpg" alt="Night Drive">
            </div>
          </div>

          <!-- 12. K-Pop & Asian Wave -->
          <div class="genre-tile" onclick="filterGenre('Kpop Hits')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #f43f5e;">FREQ 12</span>
              <h3 class="genre-title">K-Pop &amp; Asian Wave</h3>
            </div>
            <span class="genre-subtitle">NewJeans, BTS, BLACKPINK</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/synth.jpg" alt="K-Pop">
            </div>
          </div>

          <!-- 13. Latin & Reggaeton -->
          <div class="genre-tile" onclick="filterGenre('Latin Hits Reggaeton')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #f97316;">FREQ 13</span>
              <h3 class="genre-title">Latin &amp; Urbano</h3>
            </div>
            <span class="genre-subtitle">Bad Bunny, Rosalía, J Balvin</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/indie.jpg" alt="Latin">
            </div>
          </div>

          <!-- 14. Techno & Deep House -->
          <div class="genre-tile" onclick="filterGenre('Deep House Melodic Techno')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #06b6d4;">FREQ 14</span>
              <h3 class="genre-title">Techno &amp; House</h3>
            </div>
            <span class="genre-subtitle">Peggy Gou, Tale of Us, Bicep</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/idm.jpg" alt="Techno">
            </div>
          </div>

          <!-- 15. Jazz & Blues -->
          <div class="genre-tile" onclick="filterGenre('Jazz Classics')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #eab308;">FREQ 15</span>
              <h3 class="genre-title">Jazz &amp; Midnight Blues</h3>
            </div>
            <span class="genre-subtitle">Miles Davis, Chet Baker, Coltrane</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/focus.jpg" alt="Jazz">
            </div>
          </div>

          <!-- 16. Metal & Heavy -->
          <div class="genre-tile" onclick="filterGenre('Heavy Metal Rock')">
            <div class="genre-tile-header">
              <span class="genre-freq-tag" style="color: #dc2626;">FREQ 16</span>
              <h3 class="genre-title">Metal &amp; Hard Rock</h3>
            </div>
            <span class="genre-subtitle">Metallica, Deftones, Iron Maiden</span>
            <div class="genre-img-thumb">
              <img src="assets/img/genres/nightdrive.jpg" alt="Metal">
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- VIEW 2B: USER LIBRARY VIEW (USER ALBUMS / PLAYLISTS & LIKED SONGS) -->
    <div id="view-library">
      <!-- Library Grid Overview -->
      <div id="library-grid-section">
        <!-- Community Albums & Playlists from YouTube Music (above User Library) -->
        <section id="library-community-section" style="margin-bottom: 28px;">
          <div class="section-header">
            <div class="section-title-group">
              <span class="signal-dot"></span>
              <h2 class="section-title">COMMUNITY ALBUMS &amp; PLAYLISTS · YOUTUBE MUSIC</h2>
            </div>
            <div class="library-header-actions">
              <span class="section-meta">COMMUNITY CURATED</span>
              <button type="button" class="btn-refresh-community" id="btn-refresh-community" title="Refresh community albums">
                ↻ REFRESH
              </button>
            </div>
          </div>

          <div class="albums-grid" id="community-albums-grid">
            <!-- Populated dynamically via player.js from YouTube Music Community Playlists -->
          </div>
        </section>

        <!-- Personal User Albums & Playlists -->
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h2 class="section-title">YOUR LIBRARY · USER ALBUMS &amp; PLAYLISTS</h2>
          </div>
          <div class="library-header-actions">
            <span class="section-meta" id="library-meta-count">0 COLLECTIONS</span>
            <button type="button" class="btn-library-create" id="btn-library-new-playlist">
              + NEW PLAYLIST
            </button>
          </div>
        </div>

        <div class="albums-grid" id="library-grid">
          <!-- Populated dynamically via player.js with Liked Songs & User-Created Playlists -->
        </div>
      </div>

      <!-- Playlist / User Album Detail View -->
      <div id="library-playlist-detail" style="display: none;">
        <div class="artist-nav-bar">
          <button type="button" class="btn-back-overview" id="btn-back-library">
            ← BACK TO LIBRARY
          </button>
          <span class="chassis-label" id="playlist-detail-module-tag">CHASSIS MODULE: PLAYLIST_ARCHIVE</span>
        </div>

        <div class="artist-hero-card playlist-hero-card">
          <div class="artist-hero-photo-wrap" id="playlist-detail-cover-wrap">
            <img id="playlist-detail-cover" class="artist-hero-photo" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23181818'/%3E%3Ccircle cx='100' cy='100' r='55' fill='none' stroke='%23383838' stroke-width='4'/%3E%3Ccircle cx='100' cy='100' r='14' fill='%23FF4F00'/%3E%3C/svg%3E" alt="Playlist Cover">
            <div class="artist-ref-tag" id="playlist-detail-badge">USER ALBUM</div>
          </div>

          <div class="artist-hero-details">
            <div>
              <div class="artist-meta-location">
                <span class="signal-dot"></span>
                <span id="playlist-detail-type">PERSONAL COLLECTION</span>
              </div>
              <h1 class="artist-display-name" id="playlist-detail-title">MY PLAYLIST</h1>
              <p class="artist-bio-text" id="playlist-detail-desc">
                0 tracks saved in this collection.
              </p>
            </div>

            <div class="artist-action-row">
              <button type="button" class="btn-play-all" id="btn-play-all-playlist">
                ▶ PLAY ALL
              </button>
              <button type="button" class="btn-delete-playlist" id="btn-delete-playlist" style="display: none;">
                DELETE PLAYLIST
              </button>
              <span class="catalog-id-readout" id="playlist-detail-count-badge">
                0 TRACKS
              </span>
            </div>
          </div>
        </div>

        <section>
          <div class="section-header">
            <div class="section-title-group">
              <span class="signal-dot"></span>
              <h3 class="section-title" id="playlist-tracks-heading">SAVED TRACKS</h3>
            </div>
            <span class="section-meta" id="playlist-tracks-meta">0 TRACKS</span>
          </div>

          <div class="tracks-table-wrap">
            <table class="tracks-table">
              <thead>
                <tr>
                  <th class="col-num">#</th>
                  <th class="col-title">TITLE</th>
                  <th class="col-artist">ARTIST</th>
                  <th class="col-dur">DURATION</th>
                  <th class="col-action">ACTION</th>
                </tr>
              </thead>
              <tbody id="playlist-tracks-body">
                <!-- Dynamically populated via player.js -->
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>

    <!-- VIEW 3: ARTIST PROFILE VIEW (IN-PLACE SWITCHABLE) -->
    <div id="view-artist">
      <div class="artist-nav-bar">
        <button type="button" class="btn-back-overview" id="btn-back-overview">
          ← BACK TO OVERVIEW
        </button>
        <span class="chassis-label">CHASSIS MODULE: ARTIST_DOSSIER_REF_01</span>
      </div>

      <!-- Industrial Header Banner -->
      <div class="artist-hero-card">
        <div class="artist-hero-photo-wrap">
          <img id="artist-photo" class="artist-hero-photo" src="https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg" alt="Artist Profile Photo">
          <div class="artist-ref-tag">REF #001</div>
        </div>

        <div class="artist-hero-details">
          <div>
            <div class="artist-meta-location">
              <span class="signal-dot"></span>
              <span id="artist-location">LONDON, UK · TRIDENT STUDIOS</span>
            </div>
            <h1 class="artist-display-name" id="artist-name">QUEEN</h1>
            <p class="artist-bio-text" id="artist-bio">
              Iconic British rock band formed in London in 1970 by Freddie Mercury, Brian May, Roger Taylor, and John Deacon. Renowned for their operatic anthems, progressive rock orchestration, and multi-layered vocal harmonies.
            </p>
          </div>

          <div class="artist-action-row">
            <button type="button" class="btn-play-all" id="btn-play-all-artist">
              ▶ PLAY ALL
            </button>
            <button type="button" class="btn-secondary-action">
              FOLLOW DISCOGRAPHY
            </button>
            <span class="catalog-id-readout" id="artist-catalog-id">
              CATALOG IDENTIFIER: QN-ARCH-1973-1991
            </span>
          </div>
        </div>
      </div>

      <!-- Engineered Discography Tracks -->
      <section>
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h3 class="section-title">ENGINEERED DISCOGRAPHY · ESSENTIAL MASTER TAPES</h3>
          </div>
          <span class="section-meta">CHRONO-CATALOG</span>
        </div>

        <div class="tracks-table-wrap">
          <table class="tracks-table">
            <thead>
              <tr>
                <th class="col-num">#</th>
                <th class="col-title">TITLE</th>
                <th class="col-artist">ALBUM</th>
                <th class="col-dur">DURATION</th>
                <th class="col-action">ACTION</th>
              </tr>
            </thead>
            <tbody id="artist-tracks-body">
              <!-- Dynamically populated via player.js -->
            </tbody>
          </table>
        </div>
      </section>
    </div>

  </main>

  <!-- ==================== BOTTOM STICKY AUDIO PLAYER BAR ==================== -->
  <footer id="player-bar">
    <!-- Native HTML5 Audio Element (Zero IFrame / 100% Ad-Free Direct Stream) -->
    <audio id="apollo-audio" preload="metadata"></audio>

    <!-- Left: Current Track Details -->
    <div class="player-left">
      <div class="now-thumb-wrap">
        <img id="now-thumb" class="now-thumb" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23181818'/%3E%3Ccircle cx='100' cy='100' r='55' fill='none' stroke='%23383838' stroke-width='4'/%3E%3Ccircle cx='100' cy='100' r='14' fill='%23FF4F00'/%3E%3C/svg%3E" alt="Track Art">
      </div>
      <div class="now-meta">
        <p class="now-title" id="now-title">Ready to Stream</p>
        <span class="now-artist" id="now-artist">YouTube Music</span>
      </div>
      <button type="button" class="btn-player-add" id="btn-now-add" title="Add to playlist">+</button>
    </div>

    <!-- Center: Transport & Timeline -->
    <div class="player-center">
      <div class="transport-buttons">
        <!-- Shuffle -->
        <button type="button" class="btn-transport" id="btn-shuffle" title="Shuffle">
          <span class="material-symbols-outlined" style="font-size: 19px;">shuffle</span>
        </button>

        <!-- Previous -->
        <button type="button" class="btn-transport" id="btn-prev" title="Previous">
          <span class="material-symbols-outlined" style="font-size: 21px;">skip_previous</span>
        </button>

        <!-- Play / Pause -->
        <button type="button" class="btn-play-pause" id="btn-play-pause" title="Play">
          <span class="material-symbols-outlined" id="play-pause-icon">play_arrow</span>
        </button>

        <!-- Next -->
        <button type="button" class="btn-transport" id="btn-next" title="Next">
          <span class="material-symbols-outlined" style="font-size: 21px;">skip_next</span>
        </button>

        <!-- Repeat (3-cycle mode: Off -> All -> One) -->
        <button type="button" class="btn-transport" id="btn-repeat" title="Repeat Off">
          <span class="material-symbols-outlined" style="font-size: 19px;">repeat</span>
          <span class="repeat-badge">1</span>
        </button>
      </div>

      <!-- Timeline Seek Bar -->
      <div class="timeline-row">
        <span class="time-readout" id="time-current">00:00</span>
        <div class="seek-bar-track" id="seek-bar-track">
          <div class="seek-bar-fill" id="seek-bar-fill"></div>
        </div>
        <span class="time-readout time-right" id="time-duration">06:00</span>
      </div>
    </div>

    <!-- Right: Queue & Volume Control -->
    <div class="player-right">
      <button type="button" class="btn-queue-toggle" id="btn-queue-toggle" title="Queue">
        <span class="material-symbols-outlined" style="font-size: 20px;">queue_music</span>
      </button>
      <button type="button" class="btn-vol" id="btn-vol" title="Mute/Unmute">
        <span class="material-symbols-outlined" id="vol-icon" style="font-size: 18px;">volume_up</span>
      </button>
      <div class="vol-bar-track" id="vol-bar-track">
        <div class="vol-bar-fill" id="vol-bar-fill"></div>
      </div>
    </div>
  </footer>

  <!-- ==================== RIGHT QUEUE PANEL (NO RECENTLY PLAYED) ==================== -->
  <aside id="queue-drawer" aria-label="Playback Queue">
    <div class="queue-drawer-header">
      <div class="queue-header-title-wrap">
        <span class="queue-header-tab">Queue</span>
      </div>
      <button type="button" class="btn-close-queue" id="btn-close-queue" title="Close Queue">
        <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
      </button>
    </div>

    <div class="queue-drawer-scroll">
      <!-- Now Playing -->
      <div class="queue-section">
        <h3 class="queue-section-title">Now playing</h3>
        <div id="queue-now-playing">
          <!-- Populated via player.js -->
        </div>
      </div>

      <!-- Manually Queued Songs ("Add to queue") -->
      <div class="queue-section" id="queue-manual-section" style="display: none;">
        <div class="queue-section-row">
          <h3 class="queue-section-title">Next in queue</h3>
          <button type="button" class="btn-clear-queue" id="btn-clear-manual-queue">Clear</button>
        </div>
        <div class="queue-tracks-list" id="queue-manual-list"></div>
      </div>

      <!-- Upcoming Context Queue ("Next from: ...") -->
      <div class="queue-section">
        <h3 class="queue-section-title" id="queue-next-from-title">Next from: YouTube Music</h3>
        <div class="queue-tracks-list" id="queue-upcoming-list">
          <!-- Populated via player.js -->
        </div>
      </div>
    </div>
  </aside>

  <!-- ==================== BOTTOM NAVIGATION (ANDROID MOBILE <768px) ==================== -->
  <nav id="bottom-nav">
    <a href="#" class="bottom-nav-item active" data-nav="home">
      <span class="material-symbols-outlined">home</span>
      <span>Home</span>
    </a>
    <a href="#" class="bottom-nav-item" data-nav="explore">
      <span class="material-symbols-outlined">explore</span>
      <span>Explore</span>
    </a>
    <a href="#" class="bottom-nav-item" data-nav="playlists">
      <span class="material-symbols-outlined">queue_music</span>
      <span>Playlists</span>
    </a>
  </nav>

  <!-- ==================== SIGNAL ACTIONS & PLAYLIST POPOVER MODAL ==================== -->
  <div id="playlist-popover">
    <div class="popover-header">
      <div style="display: flex; align-items: center; gap: 6px;">
        <span class="signal-dot"></span>
        <span class="chassis-label" id="popover-header-label">TRACK OPTIONS</span>
      </div>
      <button type="button" class="btn-close-popover" id="btn-close-popover">[✕]</button>
    </div>

    <div class="popover-target-card">
      <span class="popover-target-label">SELECTED TRACK:</span>
      <p class="popover-target-title" id="popover-target-track">Ready to Stream</p>
    </div>

    <!-- Queue Action: Add to Queue (shown when clicking ":" on tracks) -->
    <div class="popover-like-section" id="popover-queue-section">
      <div class="popover-section-label">PLAYBACK QUEUE:</div>
      <button type="button" class="btn-popover-like" id="btn-popover-add-queue">
        <span class="material-symbols-outlined" style="font-size: 16px;">queue_music</span>
        <span>+ ADD TO QUEUE</span>
      </button>
    </div>

    <!-- Option 1: Like Song -> Permanent "Liked Songs" Playlist -->
    <div class="popover-like-section" id="popover-like-section">
      <div class="popover-section-label">LIKED PLAYLIST:</div>
      <button type="button" class="btn-popover-like" id="btn-popover-like">
        <span class="material-symbols-outlined" id="popover-like-icon" style="font-size: 16px;">favorite</span>
        <span id="popover-like-text">LIKE · SAVE TO LIKED SONGS</span>
      </button>
    </div>

    <!-- Option 2: Save to User-Created Playlist -->
    <div class="popover-section-label">USER PLAYLISTS:</div>

    <div class="popover-playlists-list" id="popover-playlists-list">
      <!-- Populated via player.js -->
    </div>

    <div class="popover-new-wrap">
      <input 
        type="text" 
        id="popover-new-playlist-input" 
        class="popover-input" 
        placeholder="+ New User Playlist Name" 
        autocomplete="off"
      >
      <button type="button" class="btn-popover-create" id="btn-popover-create">
        CREATE &amp; SAVE
      </button>
    </div>

    <!-- Contextual Remove Action (when inside a playlist view) -->
    <button type="button" class="btn-popover-remove" id="btn-popover-remove-track" style="display: none;">
      ✕ REMOVE FROM THIS PLAYLIST
    </button>
  </div>

  <!-- ==================== USER LOGIN / REGISTER MODAL ==================== -->
  <div id="auth-modal-overlay" style="display: none;">
    <div id="auth-modal">
      <div class="popover-header">
        <div style="display: flex; align-items: center; gap: 6px;">
          <span class="signal-dot"></span>
          <span class="chassis-label">APOLLO USER ACCESS · MYSQL IDENTITY</span>
        </div>
        <button type="button" class="btn-close-popover" id="btn-close-auth">[✕]</button>
      </div>

      <div class="auth-tabs">
        <button type="button" class="auth-tab active" id="tab-auth-login" data-tab="login">LOGIN</button>
        <button type="button" class="auth-tab" id="tab-auth-register" data-tab="register">REGISTER</button>
      </div>

      <div id="auth-alert" class="auth-alert" style="display: none;"></div>

      <!-- Login Form -->
      <form id="form-auth-login" class="auth-form">
        <label class="auth-label" for="login-identity">USERNAME OR EMAIL</label>
        <input type="text" id="login-identity" class="popover-input auth-input" placeholder="Enter username or email" required autocomplete="username">

        <label class="auth-label" for="login-password">PASSWORD</label>
        <input type="password" id="login-password" class="popover-input auth-input" placeholder="Enter password" required autocomplete="current-password">

        <button type="submit" class="btn-popover-create auth-submit-btn" id="btn-submit-login">
          SIGN IN TO ACCOUNT
        </button>
      </form>

      <!-- Register Form -->
      <form id="form-auth-register" class="auth-form" style="display: none;">
        <label class="auth-label" for="reg-username">USERNAME</label>
        <input type="text" id="reg-username" class="popover-input auth-input" placeholder="Choose username (min. 3 chars)" required autocomplete="username">

        <label class="auth-label" for="reg-email">EMAIL ADDRESS</label>
        <input type="email" id="reg-email" class="popover-input auth-input" placeholder="you@example.com" required autocomplete="email">

        <label class="auth-label" for="reg-password">PASSWORD</label>
        <input type="password" id="reg-password" class="popover-input auth-input" placeholder="Create password (min. 4 chars)" required autocomplete="new-password">

        <button type="submit" class="btn-popover-create auth-submit-btn" id="btn-submit-register">
          CREATE ACCOUNT &amp; SIGN IN
        </button>
      </form>
    </div>
  </div>

  <!-- ==================== SYSTEM TOAST NOTIFICATION ==================== -->
  <div id="system-toast">
    <span class="signal-dot"></span>
    <span class="toast-text" id="toast-text">SIGNAL ROUTED TO PLAYLIST</span>
  </div>

</div>

<script>
  window.APOLLO_INITIAL_USER = <?= json_encode($initialUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<!-- Apollo Player Engine Script with Cache-Buster -->
<script src="assets/player.js?v=<?= filemtime(__DIR__ . '/assets/player.js') ?>"></script>
</body>
</html>
