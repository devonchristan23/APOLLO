  <!-- ==================== TRACK OPTIONS & PLAYLIST POPOVER ==================== -->
  <div id="playlist-popover">
    <div class="popover-header">
      <div style="display: flex; align-items: center; gap: 6px;">
        <span class="signal-dot"></span>
        <span class="chassis-label" id="popover-header-label">TRACK OPTIONS</span>
      </div>
      <button type="button" class="btn-close-popover" id="btn-close-popover">[✕]</button>
    </div>

    <div class="popover-target-card">
      <span class="popover-target-label">SELECTED TRACK:</span>
      <p class="popover-target-title" id="popover-target-track">Ready to Stream</p>
    </div>

    <!-- Queue Action -->
    <div class="popover-like-section" id="popover-queue-section">
      <div class="popover-section-label">PLAYBACK QUEUE:</div>
      <button type="button" class="btn-popover-like" id="btn-popover-add-queue">
        <span class="material-symbols-outlined" style="font-size: 16px;">queue_music</span>
        <span>+ ADD TO QUEUE</span>
      </button>
    </div>

    <!-- Option 1: Like Song -> Permanent "Liked Songs" Playlist -->
    <div class="popover-like-section" id="popover-like-section">
      <div class="popover-section-label">LIKED PLAYLIST:</div>
      <button type="button" class="btn-popover-like" id="btn-popover-like">
        <span class="material-symbols-outlined" id="popover-like-icon" style="font-size: 16px;">favorite</span>
        <span id="popover-like-text">LIKE · SAVE TO LIKED SONGS</span>
      </button>
    </div>

    <!-- Option 2: Save to User-Created Playlist -->
    <div class="popover-section-label">USER PLAYLISTS:</div>
    <div class="popover-playlists-list" id="popover-playlists-list"></div>

    <div class="popover-new-wrap">
      <input
        type="text"
        id="popover-new-playlist-input"
        class="popover-input"
        placeholder="+ New User Playlist Name"
        autocomplete="off"
      >
      <button type="button" class="btn-popover-create" id="btn-popover-create">
        CREATE &amp; SAVE
      </button>
    </div>

    <button type="button" class="btn-popover-remove" id="btn-popover-remove-track" style="display: none;">
      ✕ REMOVE FROM THIS PLAYLIST
    </button>
  </div>

  <!-- ==================== USER LOGIN / REGISTER MODAL ==================== -->
  <div id="auth-modal-overlay" style="display: none;">
    <div id="auth-modal">
      <div class="popover-header">
        <div style="display: flex; align-items: center; gap: 6px;">
          <span class="signal-dot"></span>
          <span class="chassis-label">APOLLO USER ACCESS · MYSQL IDENTITY</span>
        </div>
        <button type="button" class="btn-close-popover" id="btn-close-auth">[✕]</button>
      </div>

      <div class="auth-tabs">
        <button type="button" class="auth-tab active" id="tab-auth-login" data-tab="login">LOGIN</button>
        <button type="button" class="auth-tab" id="tab-auth-register" data-tab="register">REGISTER</button>
      </div>

      <div id="auth-alert" class="auth-alert" style="display: none;"></div>

      <!-- Login Form -->
      <form id="form-auth-login" class="auth-form">
        <label class="auth-label" for="login-identity">USERNAME OR EMAIL</label>
        <input type="text" id="login-identity" class="popover-input auth-input" placeholder="Enter username or email" required autocomplete="username">

        <label class="auth-label" for="login-password">PASSWORD</label>
        <input type="password" id="login-password" class="popover-input auth-input" placeholder="Enter password" required autocomplete="current-password">

        <button type="submit" class="btn-popover-create auth-submit-btn" id="btn-submit-login">
          SIGN IN TO ACCOUNT
        </button>
      </form>

      <!-- Register Form -->
      <form id="form-auth-register" class="auth-form" style="display: none;">
        <label class="auth-label" for="reg-username">USERNAME</label>
        <input type="text" id="reg-username" class="popover-input auth-input" placeholder="Choose username (min. 3 chars)" required autocomplete="username">

        <label class="auth-label" for="reg-email">EMAIL ADDRESS</label>
        <input type="email" id="reg-email" class="popover-input auth-input" placeholder="you@example.com" required autocomplete="email">

        <label class="auth-label" for="reg-password">PASSWORD</label>
        <input type="password" id="reg-password" class="popover-input auth-input" placeholder="Create password (min. 4 chars)" required autocomplete="new-password">

        <button type="submit" class="btn-popover-create auth-submit-btn" id="btn-submit-register">
          CREATE ACCOUNT &amp; SIGN IN
        </button>
      </form>
    </div>
  </div>

  <!-- ==================== SYSTEM TOAST NOTIFICATION ==================== -->
  <div id="system-toast">
    <span class="signal-dot"></span>
    <span class="toast-text" id="toast-text">SIGNAL ROUTED TO PLAYLIST</span>
  </div>
