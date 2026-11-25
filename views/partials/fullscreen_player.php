  <!-- ==================== FULLSCREEN AMBIENT NOW-PLAYING MODE ==================== -->
  <div id="fullscreen-player" aria-hidden="true">
    <!-- Multi-Layer Moving Blurred Album-Art Background -->
    <div class="fs-ambient-bg" aria-hidden="true">
      <div class="fs-bg-layer layer-a" id="fs-bg-a"></div>
      <div class="fs-bg-layer layer-b" id="fs-bg-b"></div>
      <div class="fs-bg-layer layer-c" id="fs-bg-c"></div>
      <div class="fs-bg-vignette"></div>
    </div>

    <!-- Top Control Bar -->
    <div class="fs-top-bar">
      <div class="fs-brand-status">
        <span class="signal-dot"></span>
        <span class="fs-status-text">APOLLO · FULLSCREEN AMBIENT DECK</span>
        <span class="fs-source-pill" id="fs-source-label">YOUTUBE MUSIC</span>
      </div>

      <div class="fs-top-actions">
        <button type="button" class="fs-icon-btn" id="btn-fs-native" title="Toggle monitor fullscreen">
          <span class="material-symbols-outlined" id="fs-native-icon">fullscreen</span>
        </button>
        <button type="button" class="fs-icon-btn" id="btn-fullscreen-close" title="Exit fullscreen (ESC)">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
    </div>

    <!-- Center Stage: Sharp Centered Album Artwork & Track Info -->
    <div class="fs-center-stage">
      <div class="fs-cover-wrap" id="fs-cover-wrap">
        <img
          id="fs-cover"
          class="fs-cover-img"
          referrerpolicy="no-referrer"
          src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23181818'/%3E%3Ccircle cx='100' cy='100' r='55' fill='none' stroke='%23383838' stroke-width='4'/%3E%3Ccircle cx='100' cy='100' r='14' fill='%23FF4F00'/%3E%3C/svg%3E"
          alt="Now Playing Cover"
        >
        <div class="fs-cover-glare" id="fs-cover-glare" aria-hidden="true"></div>
      </div>

      <div class="fs-track-meta">
        <h2 class="fs-track-title" id="fs-title">Ready to Stream</h2>
        <button type="button" class="fs-track-artist" id="fs-artist" title="Open artist profile">YouTube Music</button>
        <span class="fs-track-album" id="fs-album"></span>
      </div>
    </div>

    <!-- Bottom Controls Deck -->
    <div class="fs-controls-deck">
      <div class="fs-timeline-row">
        <span class="time-readout" id="fs-time-current">00:00</span>
        <div class="seek-bar-track fs-seek-track" id="fs-seek-track">
          <div class="seek-bar-fill" id="fs-seek-fill"></div>
        </div>
        <span class="time-readout time-right" id="fs-time-duration">00:00</span>
      </div>

      <div class="fs-transport-row">
        <div class="fs-transport-side left">
          <button type="button" class="btn-transport fs-side-btn" id="fs-btn-like" title="Like song">
            <span class="material-symbols-outlined" id="fs-like-icon">favorite</span>
          </button>
          <button type="button" class="btn-transport fs-side-btn" id="fs-btn-add" title="Add to playlist">
            <span class="material-symbols-outlined">playlist_add</span>
          </button>
        </div>

        <div class="fs-transport-main">
          <button type="button" class="btn-transport" id="fs-btn-shuffle" title="Shuffle">
            <span class="material-symbols-outlined" style="font-size: 22px;">shuffle</span>
          </button>
          <button type="button" class="btn-transport" id="fs-btn-prev" title="Previous">
            <span class="material-symbols-outlined" style="font-size: 28px;">skip_previous</span>
          </button>
          <button type="button" class="btn-play-pause fs-play-btn" id="fs-btn-play-pause" title="Play / Pause">
            <span class="material-symbols-outlined" id="fs-play-pause-icon" style="font-size: 30px;">play_arrow</span>
          </button>
          <button type="button" class="btn-transport" id="fs-btn-next" title="Next">
            <span class="material-symbols-outlined" style="font-size: 28px;">skip_next</span>
          </button>
          <button type="button" class="btn-transport" id="fs-btn-repeat" title="Repeat">
            <span class="material-symbols-outlined" style="font-size: 22px;">repeat</span>
            <span class="repeat-badge">1</span>
          </button>
        </div>

        <div class="fs-transport-side right">
          <button type="button" class="btn-vol" id="fs-btn-vol" title="Mute / Unmute">
            <span class="material-symbols-outlined" id="fs-vol-icon" style="font-size: 20px;">volume_up</span>
          </button>
          <div class="vol-bar-track fs-vol-track" id="fs-vol-track">
            <div class="vol-bar-fill" id="fs-vol-fill"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
