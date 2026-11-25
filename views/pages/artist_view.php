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
