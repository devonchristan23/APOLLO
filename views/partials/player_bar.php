  <!-- ==================== BOTTOM STICKY AUDIO PLAYER BAR ==================== -->
  <footer id="player-bar">
    <!-- Native HTML5 Audio Element + Headless Cloud Audio Bridge -->
    <audio id="apollo-audio" preload="metadata"></audio>
    <div id="apollo-yt-bridge-wrap" aria-hidden="true" style="position:fixed;width:1px;height:1px;bottom:0;left:0;opacity:0.001;pointer-events:none;overflow:hidden;z-index:-1;">
      <div id="apollo-yt-bridge"></div>
    </div>

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
        <button type="button" class="btn-transport" id="btn-shuffle" title="Shuffle">
          <span class="material-symbols-outlined" style="font-size: 19px;">shuffle</span>
        </button>

        <button type="button" class="btn-transport" id="btn-prev" title="Previous">
          <span class="material-symbols-outlined" style="font-size: 21px;">skip_previous</span>
        </button>

        <button type="button" class="btn-play-pause" id="btn-play-pause" title="Play">
          <span class="material-symbols-outlined" id="play-pause-icon">play_arrow</span>
        </button>

        <button type="button" class="btn-transport" id="btn-next" title="Next">
          <span class="material-symbols-outlined" style="font-size: 21px;">skip_next</span>
        </button>

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

    <!-- Right: Queue, Volume & Fullscreen Mode -->
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
      <button type="button" class="btn-queue-toggle" id="btn-fullscreen-open" title="Fullscreen Ambient Mode (F)">
        <span class="material-symbols-outlined" style="font-size: 19px;">open_in_full</span>
      </button>
    </div>
  </footer>
