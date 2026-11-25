  <!-- ==================== LEFT SIDEBAR (DESKTOP · RESIZABLE) ==================== -->
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
      </nav>

      <!-- Your Library Section (Spotify-inspired Industrial Panel · No Podcasts) -->
      <div class="playlists-section sidebar-library-panel">
        <div class="sidebar-library-header">
          <a href="#" class="sidebar-library-title-btn" data-nav="library" id="btn-sidebar-library-open" title="Open Your Library">
            <span class="material-symbols-outlined" style="font-size: 19px;">library_music</span>
            <span>Your Library</span>
          </a>

          <div class="sidebar-library-controls">
            <button type="button" class="btn-sidebar-create" id="btn-new-playlist" title="Create new playlist">
              <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
              <span class="create-label">Create</span>
            </button>
            <button type="button" class="btn-sidebar-expand" id="btn-sidebar-expand" title="Expand / collapse sidebar">
              <span class="material-symbols-outlined" style="font-size: 16px;">open_in_full</span>
            </button>
          </div>
        </div>

        <!-- Horizontal Scrollable Filter Chips (Playlists, Albums, Artists — NO Podcasts) -->
        <div class="sidebar-chips-wrap" id="sidebar-chips-wrap">
          <button type="button" class="sidebar-chips-arrow left" id="btn-sidebar-chips-prev" title="Scroll left" style="display: none;">
            <span class="material-symbols-outlined" style="font-size: 16px;">chevron_left</span>
          </button>

          <div class="sidebar-chips-track" id="sidebar-lib-chips">
            <button type="button" class="sidebar-chip" data-lib-filter="playlists">Playlists</button>
            <button type="button" class="sidebar-chip" data-lib-filter="albums">Albums</button>
            <button type="button" class="sidebar-chip" data-lib-filter="artists">Artists</button>
          </div>

          <button type="button" class="sidebar-chips-arrow right" id="btn-sidebar-chips-next" title="Scroll right">
            <span class="material-symbols-outlined" style="font-size: 16px;">chevron_right</span>
          </button>
        </div>

        <!-- Dynamic Library Items (Liked Songs, Playlists, Saved Albums, Followed Artists) -->
        <nav class="playlist-nav-list sidebar-library-list" id="sidebar-playlists-list">
          <!-- Dynamically populated via player.js -->
        </nav>
      </div>
    </div>

    <!-- Draggable Right-Edge Resize Handle -->
    <div id="sidebar-resize-handle" title="Drag to resize Your Library"></div>
  </aside>
