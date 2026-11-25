    <!-- Top Header Bar with Global Refresh & User Account Controls -->
    <header id="top-header">
      <div class="engine-status">
        <span class="signal-dot"></span>
        <span>AUDIO ENGINE: ACTIVE [44.1kHz / 24-BIT DIETER RAMS MATRIX]</span>
      </div>
      <div class="header-right-group">
        <button type="button" class="btn-header-refresh" id="btn-global-refresh" title="Refresh current page">
          <span class="material-symbols-outlined" style="font-size: 15px;">refresh</span>
          <span class="hide-on-sm">REFRESH</span>
        </button>

        <div class="engine-badge hide-on-sm">
          <span>HI-RES PCM · ZERO ADS</span>
        </div>

        <!-- Guest State -->
        <div id="auth-guest-box" style="<?= $initialUser ? 'display: none;' : 'display: inline-flex;' ?> align-items: center; gap: 8px;">
          <button type="button" class="btn-header-auth" id="btn-open-auth">
            <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
            <span>LOGIN / REGISTER</span>
          </button>
        </div>

        <!-- Authenticated User State -->
        <div id="auth-user-box" style="<?= $initialUser ? 'display: inline-flex;' : 'display: none;' ?> align-items: center; gap: 8px;">
          <div class="user-session-badge">
            <span class="signal-dot"></span>
            <span id="header-username"><?= $initialUser ? htmlspecialchars($initialUser['username']) : 'USER' ?></span>
          </div>
          <button type="button" class="btn-header-logout" id="btn-logout" title="Sign out">
            LOGOUT
          </button>
        </div>
      </div>
    </header>
