    <!-- VIEW 3: ARTIST PROFILE VIEW (SONGS + OFFICIAL ALBUMS + RELATED ARTISTS + FOLLOW) -->
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
          <img id="artist-photo" class="artist-hero-photo" referrerpolicy="no-referrer" src="https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg" alt="Artist Profile Photo">
          <div class="artist-ref-tag">ARTIST</div>
        </div>

        <div class="artist-hero-details">
          <div>
            <div class="artist-meta-location">
              <span class="signal-dot"></span>
              <span id="artist-location">YOUTUBE MUSIC · ARTIST CATALOG</span>
            </div>
            <h1 class="artist-display-name" id="artist-name">QUEEN</h1>
            <p class="artist-bio-text" id="artist-bio">
              Official songs, studio albums, and matching artists.
            </p>
          </div>

          <div class="artist-action-row">
            <button type="button" class="btn-play-all" id="btn-play-all-artist">
              ▶ PLAY ALL
            </button>
            <button type="button" class="btn-secondary-action" id="btn-follow-artist">
              + FOLLOW ARTIST
            </button>
            <span class="catalog-id-readout" id="artist-catalog-id">
              CATALOG IDENTIFIER: APOLLO-REF-01
            </span>
          </div>
        </div>
      </div>

      <!-- Official Albums of the Artist -->
      <section id="artist-albums-section">
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h3 class="section-title">ALBUMS &amp; OFFICIAL RELEASES</h3>
          </div>
          <span class="section-meta" id="artist-albums-meta">0 ALBUMS</span>
        </div>

        <div class="albums-grid" id="artist-albums-grid">
          <!-- Populated dynamically via player.js -->
        </div>
      </section>

      <!-- Engineered Discography Tracks -->
      <section>
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot"></span>
            <h3 class="section-title">TOP TRACKS · ESSENTIAL MASTER TAPES</h3>
          </div>
          <span class="section-meta" id="artist-tracks-meta">0 TRACKS</span>
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

      <!-- Matching / Related Artists -->
      <section id="artist-related-section">
        <div class="section-header">
          <div class="section-title-group">
            <span class="signal-dot" style="background-color: var(--border-line-light);"></span>
            <h3 class="section-title">ARTISTS · MATCHING &amp; RELATED</h3>
          </div>
          <span class="section-meta" id="artist-related-meta">0 ARTISTS</span>
        </div>

        <div class="albums-grid" id="artist-related-grid">
          <!-- Populated dynamically via player.js -->
        </div>
      </section>
    </div>
