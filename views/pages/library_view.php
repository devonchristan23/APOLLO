    <!-- VIEW 2B: USER LIBRARY VIEW (COMMUNITY ALBUMS, USER PLAYLISTS, SAVED ALBUMS & FOLLOWED ARTISTS) -->
    <div id="view-library">
      <!-- Library Grid Overview -->
      <div id="library-grid-section">
        <!-- Community Albums & Playlists from YouTube Music -->
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

        <!-- Personal User Library (Playlists, Albums, Followed Artists — NO Podcasts) -->
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h2 class="section-title">YOUR LIBRARY · COLLECTIONS</h2>
          </div>
          <div class="library-header-actions">
            <span class="section-meta" id="library-meta-count">0 COLLECTIONS</span>
            <button type="button" class="btn-library-create" id="btn-library-new-playlist">
              + NEW PLAYLIST
            </button>
          </div>
        </div>

        <div class="library-filter-bar" id="main-library-filters">
          <button type="button" class="sidebar-chip active" data-main-lib-filter="all">All</button>
          <button type="button" class="sidebar-chip" data-main-lib-filter="playlists">Playlists</button>
          <button type="button" class="sidebar-chip" data-main-lib-filter="albums">Albums</button>
          <button type="button" class="sidebar-chip" data-main-lib-filter="artists">Artists</button>
        </div>

        <div class="albums-grid" id="library-grid">
          <!-- Populated dynamically via player.js with Liked Songs, Playlists, Saved Albums, & Followed Artists -->
        </div>
      </div>

      <!-- Playlist / Official Album / Community Playlist Detail View -->
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
              <button type="button" class="btn-secondary-action" id="btn-save-album" style="display: none;">
                + SAVE TO LIBRARY
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
