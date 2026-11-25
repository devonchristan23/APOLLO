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

      <!-- Section: Live YouTube Music Recommended Songs -->
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
