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
