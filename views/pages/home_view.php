    <!-- VIEW 1: HOME / OVERVIEW & MULTI-TAB SEARCH -->
    <div id="view-overview">
      <!-- Search Bar + Refresh Button -->
      <div class="search-bar-wrapper">
        <div class="search-bar-row">
          <div class="search-bar">
            <span class="material-symbols-outlined search-icon">search</span>
            <input
              type="text"
              id="global-search-input"
              class="search-input"
              placeholder="Search songs, albums, artists, playlists..."
              autocomplete="off"
              spellcheck="false"
            >
            <span class="search-esc-hint">[ESC] CLEAR</span>
          </div>
          <button type="button" class="btn-search-refresh" id="btn-refresh-page" title="Refresh halaman ini">
            <span class="material-symbols-outlined" style="font-size: 19px;">refresh</span>
          </button>
        </div>
      </div>

      <!-- Section: Live YouTube Music Recommended Songs Carousel (with Left / Right Arrows) -->
      <section id="overview-albums-section">
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h2 class="section-title" id="overview-albums-title">RECOMMENDED SONGS · YOUTUBE MUSIC</h2>
          </div>
          <span class="section-meta" id="overview-albums-meta">INDIVIDUAL TRACKS · PLAY DIRECTLY</span>
        </div>

        <div class="recommended-carousel-wrap">
          <button type="button" class="rec-nav-arrow left" id="btn-rec-prev" title="Previous recommended songs" aria-label="Previous recommended songs">
            <span class="material-symbols-outlined">chevron_left</span>
          </button>

          <div class="albums-grid" id="albums-grid">
            <!-- Populated dynamically via player.js from YouTube Music -->
          </div>

          <button type="button" class="rec-nav-arrow right" id="btn-rec-next" title="Next recommended songs" aria-label="Next recommended songs">
            <span class="material-symbols-outlined">chevron_right</span>
          </button>
        </div>
      </section>

      <!-- Search Category Tabs (Visible while searching: Tracks | Albums | Artists | Playlists) -->
      <div class="search-tabs-bar" id="search-tabs-bar" style="display: none;">
        <button type="button" class="search-tab-btn active" data-search-tab="tracks">Tracks</button>
        <button type="button" class="search-tab-btn" data-search-tab="albums">Albums</button>
        <button type="button" class="search-tab-btn" data-search-tab="artists">Artists</button>
        <button type="button" class="search-tab-btn" data-search-tab="playlists">Playlists</button>
      </div>

      <!-- Section: Tracks / Search Results -->
      <section id="overview-tracks-section">
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot" style="background-color: var(--border-line-light);"></span>
            <h2 class="section-title" id="tracks-section-title">RECENTLY PLAYED / REFERENCE TRACKS</h2>
          </div>
          <span class="section-meta" id="tracks-meta-count">10 TRACKS</span>
        </div>

        <!-- Tracks Table Panel -->
        <div class="tracks-table-wrap" id="search-tracks-panel">
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

        <!-- Search Grid Panel (Albums / Artists / Playlists Tabs) -->
        <div id="search-grid-panel" style="display: none;">
          <div class="albums-grid" id="search-results-grid">
            <!-- Populated dynamically via player.js -->
          </div>
        </div>
      </section>
    </div>
