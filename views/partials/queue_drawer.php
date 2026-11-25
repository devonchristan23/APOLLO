  <!-- ==================== RIGHT QUEUE PANEL ==================== -->
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
        <div id="queue-now-playing"></div>
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
        <div class="queue-tracks-list" id="queue-upcoming-list"></div>
      </div>
    </div>
  </aside>
