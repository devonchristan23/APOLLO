/**
 * APOLLO MINIMALIST MUSIC PLAYER - CORE ENGINE
 * Swiss Grid Typography & Industrial Braun Dieter Rams Audio Aesthetic
 * Hybrid MySQL XAMPP + LocalStorage Serverless Fallback
 */

(function () {
  'use strict';

  // State Management
  const state = {
    audio: null,
    playbackBackend: 'html5', // 'html5' | 'yt_bridge'
    ytPlayer: null,
    ytApiReady: false,
    ytPendingVideoId: null,
    ytProgressTimer: null,
    ytFallbackTriedForId: null,
    isPlaying: false,
    currentTrack: null,
    queue: [],
    queueIndex: -1,
    manualQueue: [],
    queueSourceName: 'YouTube Music',
    isQueueOpen: false,
    repeatMode: 0, // 0: Off, 1: Repeat All, 2: Repeat One
    isShuffle: false,
    volume: 0.8,
    isMuted: false,
    dbConnected: false,
    currentUser: (typeof window !== 'undefined' && window.APOLLO_INITIAL_USER) ? window.APOLLO_INITIAL_USER : null,
    playlists: [],
    likedTrackIds: new Set(),
    activePlaylist: null,
    recentTracks: [],
    recentArtists: [],
    currentView: 'overview', // 'overview' | 'explore' | 'library' | 'artist'
    targetPopoverTrack: null,
    popoverTriggerEl: null,
    popoverContextPlaylist: null,
    searchDebounceTimer: null,
    currentSearchQuery: '',
    // Gapless preloading & Audio auto-recovery state
    preloadedTrack: null,
    preloadedStreamUrl: null,
    isPreloading: false,
    preloadedAudioElement: null,
    isRecoveringAudio: false,
    recoveryAttemptCount: 0
  };

  // DOM Elements
  const DOM = {
    audio: document.getElementById('apollo-audio'),
    playPauseBtn: document.getElementById('btn-play-pause'),
    playPauseIcon: document.getElementById('play-pause-icon'),
    prevBtn: document.getElementById('btn-prev'),
    nextBtn: document.getElementById('btn-next'),
    repeatBtn: document.getElementById('btn-repeat'),
    shuffleBtn: document.getElementById('btn-shuffle'),
    seekBar: document.getElementById('seek-bar-track'),
    seekFill: document.getElementById('seek-bar-fill'),
    timeCurrent: document.getElementById('time-current'),
    timeDuration: document.getElementById('time-duration'),
    volBar: document.getElementById('vol-bar-track'),
    volFill: document.getElementById('vol-bar-fill'),
    volBtn: document.getElementById('btn-vol'),
    volIcon: document.getElementById('vol-icon'),
    queueToggleBtn: document.getElementById('btn-queue-toggle'),
    queueDrawer: document.getElementById('queue-drawer'),
    btnCloseQueue: document.getElementById('btn-close-queue'),
    queueNowPlaying: document.getElementById('queue-now-playing'),
    queueManualSection: document.getElementById('queue-manual-section'),
    btnClearManualQueue: document.getElementById('btn-clear-manual-queue'),
    queueManualList: document.getElementById('queue-manual-list'),
    queueNextFromTitle: document.getElementById('queue-next-from-title'),
    queueUpcomingList: document.getElementById('queue-upcoming-list'),
    nowTitle: document.getElementById('now-title'),
    nowArtist: document.getElementById('now-artist'),
    nowThumb: document.getElementById('now-thumb'),
    nowAddBtn: document.getElementById('btn-now-add'),
    searchInput: document.getElementById('global-search-input'),
    exploreSearchInput: document.getElementById('explore-search-input'),
    viewOverview: document.getElementById('view-overview'),
    viewExplore: document.getElementById('view-explore'),
    viewLibrary: document.getElementById('view-library'),
    viewArtist: document.getElementById('view-artist'),
    overviewAlbumsSection: document.getElementById('overview-albums-section'),
    overviewAlbumsTitle: document.getElementById('overview-albums-title'),
    overviewAlbumsMeta: document.getElementById('overview-albums-meta'),
    albumsGrid: document.getElementById('albums-grid'),
    tracksTableBody: document.getElementById('tracks-table-body'),
    tracksSectionTitle: document.getElementById('tracks-section-title'),
    tracksMetaCount: document.getElementById('tracks-meta-count'),
    sidebarPlaylists: document.getElementById('sidebar-playlists-list'),
    newPlaylistBtn: document.getElementById('btn-new-playlist'),
    // Library View Elements
    libraryGridSection: document.getElementById('library-grid-section'),
    communityAlbumsGrid: document.getElementById('community-albums-grid'),
    btnRefreshCommunity: document.getElementById('btn-refresh-community'),
    libraryMetaCount: document.getElementById('library-meta-count'),
    btnLibraryNewPlaylist: document.getElementById('btn-library-new-playlist'),
    libraryGrid: document.getElementById('library-grid'),
    libraryPlaylistDetail: document.getElementById('library-playlist-detail'),
    btnBackLibrary: document.getElementById('btn-back-library'),
    playlistDetailModuleTag: document.getElementById('playlist-detail-module-tag'),
    playlistDetailCover: document.getElementById('playlist-detail-cover'),
    playlistDetailBadge: document.getElementById('playlist-detail-badge'),
    playlistDetailType: document.getElementById('playlist-detail-type'),
    playlistDetailTitle: document.getElementById('playlist-detail-title'),
    playlistDetailDesc: document.getElementById('playlist-detail-desc'),
    btnPlayAllPlaylist: document.getElementById('btn-play-all-playlist'),
    btnDeletePlaylist: document.getElementById('btn-delete-playlist'),
    playlistDetailCountBadge: document.getElementById('playlist-detail-count-badge'),
    playlistTracksHeading: document.getElementById('playlist-tracks-heading'),
    playlistTracksMeta: document.getElementById('playlist-tracks-meta'),
    playlistTracksBody: document.getElementById('playlist-tracks-body'),
    // Artist View Elements
    artistPhoto: document.getElementById('artist-photo'),
    artistName: document.getElementById('artist-name'),
    artistLocation: document.getElementById('artist-location'),
    artistBio: document.getElementById('artist-bio'),
    artistCatalogId: document.getElementById('artist-catalog-id'),
    artistTracksBody: document.getElementById('artist-tracks-body'),
    btnBackOverview: document.getElementById('btn-back-overview'),
    btnPlayAllArtist: document.getElementById('btn-play-all-artist'),
    // Popover & Toast Elements
    playlistPopover: document.getElementById('playlist-popover'),
    popoverHeaderLabel: document.getElementById('popover-header-label'),
    popoverTargetTrack: document.getElementById('popover-target-track'),
    popoverQueueSection: document.getElementById('popover-queue-section'),
    popoverAddQueueBtn: document.getElementById('btn-popover-add-queue'),
    popoverLikeSection: document.getElementById('popover-like-section'),
    popoverLikeBtn: document.getElementById('btn-popover-like'),
    popoverLikeIcon: document.getElementById('popover-like-icon'),
    popoverLikeText: document.getElementById('popover-like-text'),
    popoverList: document.getElementById('popover-playlists-list'),
    popoverInput: document.getElementById('popover-new-playlist-input'),
    popoverCreateBtn: document.getElementById('btn-popover-create'),
    popoverRemoveTrackBtn: document.getElementById('btn-popover-remove-track'),
    popoverCloseBtn: document.getElementById('btn-close-popover'),
    // User Auth Elements
    authGuestBox: document.getElementById('auth-guest-box'),
    authUserBox: document.getElementById('auth-user-box'),
    headerUsername: document.getElementById('header-username'),
    btnOpenAuth: document.getElementById('btn-open-auth'),
    btnLogout: document.getElementById('btn-logout'),
    authModalOverlay: document.getElementById('auth-modal-overlay'),
    btnCloseAuth: document.getElementById('btn-close-auth'),
    tabAuthLogin: document.getElementById('tab-auth-login'),
    tabAuthRegister: document.getElementById('tab-auth-register'),
    authAlert: document.getElementById('auth-alert'),
    formAuthLogin: document.getElementById('form-auth-login'),
    loginIdentity: document.getElementById('login-identity'),
    loginPassword: document.getElementById('login-password'),
    formAuthRegister: document.getElementById('form-auth-register'),
    regUsername: document.getElementById('reg-username'),
    regEmail: document.getElementById('reg-email'),
    regPassword: document.getElementById('reg-password'),
    systemToast: document.getElementById('system-toast'),
    toastText: document.getElementById('toast-text')
  };

  // Minimalist Vinyl Disc SVG Fallback (No local static album jpg files)
  const DEFAULT_FALLBACK_THUMB = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23181818'/%3E%3Ccircle cx='100' cy='100' r='55' fill='none' stroke='%23383838' stroke-width='4'/%3E%3Ccircle cx='100' cy='100' r='14' fill='%23FF4F00'/%3E%3C/svg%3E";
  const LIKED_PLAYLIST_NAME = 'Liked Songs';

  // Empty Default Playlists (User starts completely fresh)
  const DEFAULT_PLAYLISTS = [];

  // Live YouTube Music Recommendations & Community Playlists Cache (in-memory)
  let liveRecommendedAlbums = [];
  let liveRecommendedTracks = [];
  let communityPlaylistsCache = [];

  /**
   * Initialize Apollo Player
   */
  async function init() {
    state.audio = DOM.audio || new Audio();
    state.audio.volume = state.volume;

    // Load Local Storage Data
    loadLocalStorageData();

    // Attach Event Listeners immediately so UI is responsive
    setupAudioListeners();
    setupUIListeners();
    setupMediaSession();

    // Load Playlists & Live YouTube Music Recommendations in parallel
    await Promise.all([
      loadPlaylists(),
      loadHomeRecommendations()
    ]);

    // Pre-warm headless YT Audio Bridge script silently so cloud/Vercel first-click playback is instant
    setTimeout(() => ensureYtBridgeLoaded(), 600);
  }

  /**
   * Playlist Helpers ("Liked Songs" permanent playlist + User Playlists)
   */
  function isLikedPlaylist(pl) {
    if (!pl) return false;
    return Boolean(pl.is_liked) || String(pl.name || '').trim().toLowerCase() === 'liked songs';
  }

  function sortPlaylistsInPlace() {
    state.playlists.sort((a, b) => {
      const aLiked = isLikedPlaylist(a) ? 0 : 1;
      const bLiked = isLikedPlaylist(b) ? 0 : 1;
      if (aLiked !== bLiked) return aLiked - bLiked;
      return Number(a.id || 0) - Number(b.id || 0);
    });
  }

  function getLocalStorageScope() {
    if (state.currentUser && state.currentUser.username) {
      return 'user_' + String(state.currentUser.username).trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');
    }
    return 'guest';
  }

  function getPlaylistsStorageKey() {
    return `apollo_playlists_${getLocalStorageScope()}`;
  }

  function getPlaylistTracksStorageKey(playlistId) {
    return `apollo_pl_${getLocalStorageScope()}_${playlistId}_tracks`;
  }

  function savePlaylistsToLocalStorage() {
    if (state.dbConnected) return;
    try {
      localStorage.setItem(getPlaylistsStorageKey(), JSON.stringify(state.playlists));
    } catch (e) {}
  }

  /**
   * LocalStorage Load & Save Helpers
   */
  function loadLocalStorageData() {
    try {
      if (localStorage.getItem('apollo_clean_v4') !== 'true') {
        localStorage.removeItem('apollo_recent_tracks');
        localStorage.removeItem('apollo_recent_artists');
        localStorage.setItem('apollo_playlists', JSON.stringify([]));
        localStorage.setItem('apollo_clean_v4', 'true');
      }

      // Restore local browser account session when running serverless (without MySQL)
      if (!state.currentUser) {
        const savedSession = localStorage.getItem('apollo_local_session');
        if (savedSession) {
          const parsedUser = JSON.parse(savedSession);
          if (parsedUser && parsedUser.username) {
            state.currentUser = parsedUser;
          }
        }
      }

      // Migrate legacy unscoped guest playlists once if present
      const legacyPlaylists = localStorage.getItem('apollo_playlists');
      if (legacyPlaylists && !localStorage.getItem('apollo_playlists_guest')) {
        localStorage.setItem('apollo_playlists_guest', legacyPlaylists);
      }

      const savedTracks = localStorage.getItem('apollo_recent_tracks');
      if (savedTracks) {
        const parsed = JSON.parse(savedTracks) || [];
        // Remove any legacy cached tracks that referenced deleted local assets/img/albums files
        state.recentTracks = parsed.filter(t => t && (!t.thumbnail || !String(t.thumbnail).startsWith('assets/img/albums/')));
        localStorage.setItem('apollo_recent_tracks', JSON.stringify(state.recentTracks));
      }
      const savedArtists = localStorage.getItem('apollo_recent_artists');
      if (savedArtists) {
        state.recentArtists = JSON.parse(savedArtists);
      }
    } catch (e) {
      console.warn('LocalStorage access issue:', e);
    }
  }

  function saveRecentTrack(track) {
    if (!track || !track.title) return;

    const existingIndex = state.recentTracks.findIndex(t => t.video_id === track.video_id);
    if (existingIndex !== -1) {
      state.recentTracks.splice(existingIndex, 1);
    }
    state.recentTracks.unshift(track);
    if (state.recentTracks.length > 30) {
      state.recentTracks.pop();
    }
    try {
      localStorage.setItem('apollo_recent_tracks', JSON.stringify(state.recentTracks));
    } catch (e) {}

    if (track.artist) {
      const artName = track.artist.trim();
      const existingArt = state.recentArtists.findIndex(a => a.name.toLowerCase() === artName.toLowerCase());
      if (existingArt !== -1) {
        state.recentArtists.splice(existingArt, 1);
      }
      state.recentArtists.unshift({
        name: artName.toUpperCase(),
        key: artName,
        location: 'RECENTLY LISTENED',
        photo: track.thumbnail || DEFAULT_FALLBACK_THUMB,
        genre: 'AUDIO RECORDING'
      });
      if (state.recentArtists.length > 8) {
        state.recentArtists.pop();
      }
      try {
        localStorage.setItem('apollo_recent_artists', JSON.stringify(state.recentArtists));
      } catch (e) {}
    }
  }

  /**
   * Fetch tracks for a playlist (MySQL or LocalStorage)
   */
  async function fetchPlaylistTracks(playlistId) {
    if (state.dbConnected) {
      try {
        const res = await fetch(`api/playlist.php?action=get_playlist_tracks&playlist_id=${encodeURIComponent(playlistId)}`);
        const data = await res.json();
        if (data.status === 'ok' && Array.isArray(data.tracks)) {
          return data.tracks;
        }
      } catch (e) {}
      return [];
    }

    try {
      const local = localStorage.getItem(getPlaylistTracksStorageKey(playlistId))
        || (getLocalStorageScope() === 'guest' ? localStorage.getItem(`apollo_pl_${playlistId}_tracks`) : null);
      if (local) {
        const parsed = JSON.parse(local);
        if (Array.isArray(parsed)) return parsed;
      }
    } catch (e) {}
    return [];
  }

  /**
   * Hybrid Playlist Management (MySQL XAMPP + Scoped LocalStorage on Vercel)
   */
  async function loadPlaylists() {
    let loadedFromDb = false;
    try {
      const res = await fetch('api/playlist.php?action=get_playlists');
      if (res.ok) {
        const data = await res.json();
        if (data.status === 'ok' && data.db_connected && Array.isArray(data.playlists)) {
          state.dbConnected = true;
          state.playlists = data.playlists.map(pl => ({
            ...pl,
            track_count: Number(pl.track_count || 0),
            is_liked: isLikedPlaylist(pl)
          }));
          loadedFromDb = true;
        }
      }
    } catch (e) {
      // Offline or Serverless fallback
    }

    if (!loadedFromDb) {
      state.dbConnected = false;
      const plKey = getPlaylistsStorageKey();
      try {
        const local = localStorage.getItem(plKey);
        if (local) {
          state.playlists = JSON.parse(local);
        } else {
          state.playlists = [...DEFAULT_PLAYLISTS];
          localStorage.setItem(plKey, JSON.stringify(state.playlists));
        }
      } catch (e) {
        state.playlists = [...DEFAULT_PLAYLISTS];
      }

      // Sync track counts & cover art from scoped localStorage tracks
      state.playlists = state.playlists.map(pl => {
        let plTracks = [];
        try {
          const raw = localStorage.getItem(getPlaylistTracksStorageKey(pl.id))
            || (getLocalStorageScope() === 'guest' ? localStorage.getItem(`apollo_pl_${pl.id}_tracks`) : null);
          if (raw) plTracks = JSON.parse(raw) || [];
        } catch (e) {}
        const lastWithThumb = [...plTracks].reverse().find(t => t && t.thumbnail);
        return {
          ...pl,
          is_liked: isLikedPlaylist(pl),
          track_count: plTracks.length,
          cover_thumb: lastWithThumb ? lastWithThumb.thumbnail : (pl.cover_thumb || null)
        };
      });
    }

    sortPlaylistsInPlace();

    // Hydrate likedTrackIds from "Liked Songs" playlist if it exists
    state.likedTrackIds.clear();
    const likedPl = state.playlists.find(isLikedPlaylist);
    if (likedPl) {
      const likedTracks = await fetchPlaylistTracks(likedPl.id);
      likedTracks.forEach(t => {
        if (t && t.video_id) state.likedTrackIds.add(t.video_id);
      });
      likedPl.track_count = likedTracks.length;
      const latestCover = [...likedTracks].reverse().find(t => t && t.thumbnail);
      if (latestCover) likedPl.cover_thumb = latestCover.thumbnail;
    }

    renderSidebarPlaylists();
  }

  function renderSidebarPlaylists() {
    if (!DOM.sidebarPlaylists) return;
    DOM.sidebarPlaylists.innerHTML = '';
    sortPlaylistsInPlace();

    if (state.playlists.length === 0) {
      const emptyItem = document.createElement('div');
      emptyItem.style.cssText = 'padding: 8px 10px; font-family: var(--font-mono); font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.05em;';
      emptyItem.textContent = '[ NO PLAYLISTS SAVED ]';
      DOM.sidebarPlaylists.appendChild(emptyItem);
      return;
    }

    state.playlists.forEach(pl => {
      const liked = isLikedPlaylist(pl);
      const a = document.createElement('a');
      a.className = 'playlist-link';
      if (state.activePlaylist && String(state.activePlaylist.id) === String(pl.id) && state.currentView === 'library') {
        a.classList.add('active');
      }
      a.href = '#';
      a.dataset.id = pl.id;
      a.innerHTML = `
        <span class="truncate" style="display: inline-flex; align-items: center; gap: 6px; min-width: 0;">
          ${liked ? '<span class="material-symbols-outlined" style="font-size: 13px; color: var(--signal-orange); font-variation-settings: \'FILL\' 1;">favorite</span>' : ''}
          <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(liked ? LIKED_PLAYLIST_NAME : pl.name)}</span>
        </span>
        <span style="font-family: var(--font-mono); font-size: 10px; color: var(--text-dim); flex-shrink: 0;">${pl.track_count || 0}</span>
      `;
      a.addEventListener('click', (e) => {
        e.preventDefault();
        openPlaylistView(pl);
      });
      DOM.sidebarPlaylists.appendChild(a);
    });
  }

  /**
   * Ensure permanent English "Liked Songs" playlist exists when liking a song
   */
  async function ensureLikedPlaylist() {
    let likedPl = state.playlists.find(isLikedPlaylist);
    if (likedPl) return likedPl;

    likedPl = await createPlaylist(LIKED_PLAYLIST_NAME, {
      isSystemLiked: true,
      silentToast: true,
      description: 'Permanent collection of liked songs'
    });
    return likedPl;
  }

  async function createPlaylist(name, opts = {}) {
    if (!name || !name.trim()) return null;
    const rawTrimmed = name.trim();
    const isCreatingLiked = opts.isSystemLiked || rawTrimmed.toLowerCase() === 'liked songs';

    // Prevent duplicate "Liked Songs"
    if (isCreatingLiked) {
      const existingLiked = state.playlists.find(isLikedPlaylist);
      if (existingLiked) {
        if (!opts.silentToast) {
          showToast(`PLAYLIST [${LIKED_PLAYLIST_NAME.toUpperCase()}] IS ALREADY ACTIVE`);
        }
        return existingLiked;
      }
    }

    const cleanName = isCreatingLiked ? LIKED_PLAYLIST_NAME : rawTrimmed;
    const desc = opts.description || (isCreatingLiked ? 'Permanent collection of liked songs' : 'User created album / playlist');

    if (state.dbConnected) {
      try {
        const res = await fetch('api/playlist.php?action=create_playlist', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name: cleanName, description: desc })
        });
        const data = await res.json();
        if (data.status === 'ok' && data.playlist) {
          const created = {
            ...data.playlist,
            name: isCreatingLiked ? LIKED_PLAYLIST_NAME : data.playlist.name,
            track_count: Number(data.playlist.track_count || 0),
            cover_thumb: data.playlist.cover_thumb || null,
            is_liked: isCreatingLiked
          };
          if (!state.playlists.some(p => String(p.id) === String(created.id))) {
            state.playlists.push(created);
          }
          sortPlaylistsInPlace();
          renderSidebarPlaylists();
          if (state.currentView === 'library') renderLibraryGrid();
          if (!opts.silentToast) {
            showToast(`PLAYLIST [${cleanName.toUpperCase()}] CREATED`);
          }
          return created;
        }
      } catch (e) {}
    }

    // LocalStorage fallback
    const newPl = {
      id: isCreatingLiked ? 'liked_songs' : Date.now(),
      name: cleanName,
      description: desc,
      track_count: 0,
      cover_thumb: null,
      is_liked: isCreatingLiked
    };
    state.playlists.push(newPl);
    sortPlaylistsInPlace();
    savePlaylistsToLocalStorage();
    renderSidebarPlaylists();
    if (state.currentView === 'library') renderLibraryGrid();
    if (!opts.silentToast) {
      showToast(`PLAYLIST [${cleanName.toUpperCase()}] CREATED`);
    }
    return newPl;
  }

  async function deletePlaylist(playlistId) {
    const targetPl = state.playlists.find(p => String(p.id) === String(playlistId));
    if (!targetPl) return;

    // Liked Songs CANNOT be deleted
    if (isLikedPlaylist(targetPl)) {
      showToast('PROTECTED: "LIKED SONGS" CANNOT BE DELETED');
      return;
    }

    if (!window.confirm(`Delete playlist "${targetPl.name}" from your Library?`)) {
      return;
    }

    if (state.dbConnected) {
      try {
        const res = await fetch('api/playlist.php?action=delete_playlist', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: targetPl.id })
        });
        const data = await res.json();
        if (data.status !== 'ok') {
          showToast(data.message || 'FAILED TO DELETE PLAYLIST');
          return;
        }
      } catch (e) {
        showToast('ERROR DELETING PLAYLIST');
        return;
      }
    } else {
      try {
        localStorage.removeItem(getPlaylistTracksStorageKey(targetPl.id));
      } catch (e) {}
    }

    state.playlists = state.playlists.filter(p => String(p.id) !== String(targetPl.id));
    if (state.activePlaylist && String(state.activePlaylist.id) === String(targetPl.id)) {
      state.activePlaylist = null;
    }
    savePlaylistsToLocalStorage();
    renderSidebarPlaylists();
    openLibraryHome();
    showToast(`PLAYLIST [${targetPl.name.toUpperCase()}] DELETED`);
  }

  async function addTrackToPlaylist(playlistId, track, opts = {}) {
    if (!track || !track.video_id) return false;
    const targetPl = state.playlists.find(p => String(p.id) === String(playlistId));
    const plName = targetPl ? targetPl.name : 'PLAYLIST';

    if (state.dbConnected) {
      try {
        const res = await fetch('api/playlist.php?action=add_track', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            playlist_id: playlistId,
            video_id: track.video_id,
            title: track.title,
            artist: track.artist,
            album: track.album || '',
            duration: track.duration || '03:30',
            thumbnail: track.thumbnail || ''
          })
        });
        const data = await res.json();
        if (data.status === 'ok') {
          if (targetPl) {
            if (!data.exists) {
              targetPl.track_count = Number(targetPl.track_count || 0) + 1;
            }
            if (track.thumbnail) {
              targetPl.cover_thumb = track.thumbnail;
            }
            if (isLikedPlaylist(targetPl)) {
              state.likedTrackIds.add(track.video_id);
            }
          }
          renderSidebarPlaylists();
          if (state.currentView === 'library') {
            if (state.activePlaylist && String(state.activePlaylist.id) === String(playlistId)) {
              await openPlaylistView(targetPl);
            } else if (DOM.libraryGridSection.style.display !== 'none') {
              renderLibraryGrid();
            }
          }
          if (!opts.silentToast) {
            showToast(data.exists
              ? `"${track.title}" IS ALREADY IN [${plName.toUpperCase()}]`
              : `"${track.title}" SAVED TO [${plName.toUpperCase()}]`
            );
          }
          return true;
        }
      } catch (e) {}
    }

    // LocalStorage fallback
    const storageKey = getPlaylistTracksStorageKey(playlistId);
    let tracks = [];
    try {
      const existing = localStorage.getItem(storageKey);
      if (existing) tracks = JSON.parse(existing) || [];
    } catch (e) {}

    const alreadyExists = tracks.some(t => t.video_id === track.video_id);
    if (!alreadyExists) {
      tracks.push(track);
      try {
        localStorage.setItem(storageKey, JSON.stringify(tracks));
      } catch (e) {}
    }

    if (targetPl) {
      targetPl.track_count = tracks.length;
      if (track.thumbnail) {
        targetPl.cover_thumb = track.thumbnail;
      }
      if (isLikedPlaylist(targetPl)) {
        state.likedTrackIds.add(track.video_id);
      }
      savePlaylistsToLocalStorage();
      renderSidebarPlaylists();
      if (state.currentView === 'library') {
        if (state.activePlaylist && String(state.activePlaylist.id) === String(playlistId)) {
          await openPlaylistView(targetPl);
        } else if (DOM.libraryGridSection.style.display !== 'none') {
          renderLibraryGrid();
        }
      }
    }

    if (!opts.silentToast) {
      showToast(alreadyExists
        ? `"${track.title}" IS ALREADY IN [${plName.toUpperCase()}]`
        : `"${track.title}" SAVED TO [${plName.toUpperCase()}]`
      );
    }
    return true;
  }

  async function removeTrackFromPlaylist(playlistId, videoId, opts = {}) {
    if (!playlistId || !videoId) return;
    const targetPl = state.playlists.find(p => String(p.id) === String(playlistId));

    if (state.dbConnected) {
      try {
        await fetch('api/playlist.php?action=remove_track', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ playlist_id: playlistId, video_id: videoId })
        });
      } catch (e) {}
    } else {
      const storageKey = getPlaylistTracksStorageKey(playlistId);
      try {
        const raw = localStorage.getItem(storageKey);
        if (raw) {
          const filtered = (JSON.parse(raw) || []).filter(t => t.video_id !== videoId);
          localStorage.setItem(storageKey, JSON.stringify(filtered));
        }
      } catch (e) {}
    }

    const updatedTracks = await fetchPlaylistTracks(playlistId);
    if (targetPl) {
      targetPl.track_count = updatedTracks.length;
      const latestWithThumb = [...updatedTracks].reverse().find(t => t && t.thumbnail);
      targetPl.cover_thumb = latestWithThumb ? latestWithThumb.thumbnail : null;
      if (isLikedPlaylist(targetPl)) {
        state.likedTrackIds.delete(videoId);
      }
      savePlaylistsToLocalStorage();
    }

    renderSidebarPlaylists();
    if (state.currentView === 'library') {
      if (state.activePlaylist && String(state.activePlaylist.id) === String(playlistId) && targetPl) {
        await openPlaylistView(targetPl);
      } else {
        renderLibraryGrid();
      }
    }

    if (!opts.silentToast && targetPl) {
      showToast(`TRACK REMOVED FROM [${targetPl.name.toUpperCase()}]`);
    }
  }

  /**
   * Toggle Like on a track -> creates/updates permanent English "Liked Songs" playlist
   */
  async function toggleLikeTrack(track) {
    if (!track || !track.video_id) return;
    const isAlreadyLiked = state.likedTrackIds.has(track.video_id);

    if (!isAlreadyLiked) {
      const likedPl = await ensureLikedPlaylist();
      if (!likedPl) return;
      await addTrackToPlaylist(likedPl.id, track, { silentToast: true });
      state.likedTrackIds.add(track.video_id);
      showToast(`LIKED · SAVED TO [${LIKED_PLAYLIST_NAME.toUpperCase()}]`);
    } else {
      const likedPl = state.playlists.find(isLikedPlaylist);
      if (likedPl) {
        await removeTrackFromPlaylist(likedPl.id, track.video_id, { silentToast: true });
      }
      state.likedTrackIds.delete(track.video_id);
      showToast(`UNLIKED · REMOVED FROM [${LIKED_PLAYLIST_NAME.toUpperCase()}]`);
    }
  }

  /**
   * Open User Library Grid (No Recommended Master Albums — shows User Albums & Liked Songs)
   */
  function openLibraryHome() {
    state.activePlaylist = null;
    setActiveNavHighlight('library');
    switchView('library');
    if (DOM.libraryPlaylistDetail) DOM.libraryPlaylistDetail.style.display = 'none';
    if (DOM.libraryGridSection) DOM.libraryGridSection.style.display = 'block';
    renderSidebarPlaylists();
    renderLibraryGrid();
    loadCommunityPlaylists(false);
  }

  /**
   * Load & Render YouTube Music Community Albums / Playlists above User Library
   */
  async function loadCommunityPlaylists(forceRefresh = false) {
    if (!DOM.communityAlbumsGrid) return;

    if (!forceRefresh && communityPlaylistsCache.length > 0) {
      renderCommunityAlbumsGrid(communityPlaylistsCache);
      return;
    }

    DOM.communityAlbumsGrid.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 24px; text-align: center; border: 1px solid var(--border-line); background: var(--bg-darker); font-family: var(--font-mono); font-size: 11px; color: var(--signal-orange); letter-spacing: 0.08em;">
        [ LOADING COMMUNITY ALBUMS FROM YOUTUBE MUSIC... ]
      </div>
    `;

    try {
      const res = await fetch(`api/community.php?action=list&t=${Date.now()}`);
      const data = await res.json();
      if (data.status === 'ok' && Array.isArray(data.playlists)) {
        communityPlaylistsCache = data.playlists;
      }
    } catch (e) {
      console.warn('Community playlists fetch failed:', e);
    }

    renderCommunityAlbumsGrid(communityPlaylistsCache);
  }

  function renderCommunityAlbumsGrid(items) {
    if (!DOM.communityAlbumsGrid) return;
    DOM.communityAlbumsGrid.innerHTML = '';

    if (!items || items.length === 0) {
      DOM.communityAlbumsGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 20px; text-align: center; border: 1px solid var(--border-line); font-family: var(--font-mono); font-size: 11px; color: var(--text-dim);">
          NO COMMUNITY ALBUMS AVAILABLE RIGHT NOW
        </div>
      `;
      return;
    }

    items.slice(0, 4).forEach(comm => {
      const card = document.createElement('article');
      card.className = 'album-card';

      card.innerHTML = `
        <div class="album-thumb-wrap">
          <img class="album-thumb" referrerpolicy="no-referrer" src="${escapeHtml(comm.artwork || DEFAULT_FALLBACK_THUMB)}" alt="" loading="lazy">
          <button type="button" class="album-play-btn" title="Play ${escapeHtml(comm.title)}">
            <span class="material-symbols-outlined" style="font-size: 22px;">play_arrow</span>
          </button>
          <span class="album-badge">COMMUNITY</span>
        </div>
        <div class="album-info">
          <h3 class="album-title" title="${escapeHtml(comm.title)}">${escapeHtml(comm.title)}</h3>
          <p class="album-artist">${escapeHtml(comm.creator || 'YouTube Music Community')}</p>
        </div>
      `;

      const imgEl = card.querySelector('.album-thumb');
      if (imgEl) {
        imgEl.addEventListener('error', () => {
          imgEl.src = DEFAULT_FALLBACK_THUMB;
        });
      }

      // Play entire community album immediately when clicking ▶
      const playBtn = card.querySelector('.album-play-btn');
      if (playBtn) {
        playBtn.addEventListener('click', async (e) => {
          e.stopPropagation();
          showToast(`LOADING COMMUNITY ALBUM: [${comm.title.toUpperCase()}]`);
          try {
            const res = await fetch(`api/community.php?action=tracks&browse_id=${encodeURIComponent(comm.browse_id)}`);
            const data = await res.json();
            if (data.status === 'ok' && Array.isArray(data.tracks) && data.tracks.length > 0) {
              playTrack(data.tracks[0], data.tracks, { sourceName: comm.title });
            } else {
              showToast('COULD NOT LOAD COMMUNITY ALBUM TRACKS');
            }
          } catch (err) {
            showToast('ERROR LOADING COMMUNITY ALBUM');
          }
        });
      }

      // Open full community album tracklist inside Library detail view on card click
      card.addEventListener('click', () => {
        openCommunityPlaylistView(comm);
      });

      DOM.communityAlbumsGrid.appendChild(card);
    });
  }

  async function openCommunityPlaylistView(comm) {
    if (!comm || !comm.browse_id) return;
    state.activePlaylist = null;

    setActiveNavHighlight('library');
    switchView('library');
    if (DOM.libraryGridSection) DOM.libraryGridSection.style.display = 'none';
    if (DOM.libraryPlaylistDetail) DOM.libraryPlaylistDetail.style.display = 'block';

    if (DOM.playlistDetailTitle) DOM.playlistDetailTitle.textContent = comm.title.toUpperCase();
    if (DOM.playlistDetailType) DOM.playlistDetailType.textContent = 'YOUTUBE MUSIC · COMMUNITY ALBUM';
    if (DOM.playlistDetailBadge) DOM.playlistDetailBadge.textContent = 'COMMUNITY';
    if (DOM.playlistDetailModuleTag) DOM.playlistDetailModuleTag.textContent = 'YOUTUBE MUSIC: COMMUNITY_PLAYLIST';
    if (DOM.playlistDetailDesc) DOM.playlistDetailDesc.textContent = `Curated by ${comm.creator || 'YouTube Music Community'} · Loading tracks...`;
    if (DOM.playlistDetailCover) {
      DOM.playlistDetailCover.referrerPolicy = 'no-referrer';
      DOM.playlistDetailCover.onerror = () => { DOM.playlistDetailCover.src = DEFAULT_FALLBACK_THUMB; };
      DOM.playlistDetailCover.src = comm.artwork || DEFAULT_FALLBACK_THUMB;
      DOM.playlistDetailCover.style.filter = 'none';
    }
    if (DOM.btnDeletePlaylist) {
      DOM.btnDeletePlaylist.style.display = 'none';
      DOM.btnDeletePlaylist.onclick = null;
    }
    if (DOM.playlistTracksHeading) {
      DOM.playlistTracksHeading.textContent = `${comm.title.toUpperCase()} · COMMUNITY TRACKLIST`;
    }
    if (DOM.playlistTracksMeta) DOM.playlistTracksMeta.textContent = 'LOADING...';
    if (DOM.playlistTracksBody) {
      DOM.playlistTracksBody.innerHTML = `
        <tr>
          <td colspan="5" style="text-align: center; padding: 36px; font-family: var(--font-mono); color: var(--signal-orange); font-size: 11px; letter-spacing: 1px;">
            [ LOADING TRACKLIST FOR "${escapeHtml(comm.title.toUpperCase())}"... ]
          </td>
        </tr>
      `;
    }

    let tracks = [];
    try {
      const res = await fetch(`api/community.php?action=tracks&browse_id=${encodeURIComponent(comm.browse_id)}`);
      const data = await res.json();
      if (data.status === 'ok' && Array.isArray(data.tracks)) {
        tracks = data.tracks;
      }
    } catch (e) {}

    if (DOM.playlistDetailDesc) {
      DOM.playlistDetailDesc.textContent = `Curated by ${comm.creator || 'YouTube Music Community'} · ${tracks.length} ${tracks.length === 1 ? 'track' : 'tracks'}.`;
    }
    if (DOM.playlistDetailCountBadge) {
      DOM.playlistDetailCountBadge.textContent = `${tracks.length} ${tracks.length === 1 ? 'TRACK' : 'TRACKS'}`;
    }
    if (DOM.playlistTracksMeta) {
      DOM.playlistTracksMeta.textContent = `${tracks.length} ${tracks.length === 1 ? 'TRACK' : 'TRACKS'}`;
    }
    if (DOM.btnPlayAllPlaylist) {
      DOM.btnPlayAllPlaylist.onclick = () => {
        if (tracks.length > 0) {
          playTrack(tracks[0], tracks, { sourceName: comm.title });
        } else {
          showToast('NO PLAYABLE TRACKS FOUND');
        }
      };
    }

    // Pass null as playlistObj so tracks are read-only community tracks (can still Queue, Like, or Save to user playlist!)
    renderPlaylistDetailTracks(tracks, null);
  }

  function renderLibraryGrid() {
    if (!DOM.libraryGrid) return;
    DOM.libraryGrid.innerHTML = '';
    sortPlaylistsInPlace();

    const total = state.playlists.length;
    if (DOM.libraryMetaCount) {
      DOM.libraryMetaCount.textContent = `${total} ${total === 1 ? 'ALBUM / PLAYLIST' : 'ALBUMS / PLAYLISTS'}`;
    }

    if (total === 0) {
      const emptyBox = document.createElement('div');
      emptyBox.className = 'library-empty-state';
      emptyBox.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 36px; color: var(--signal-orange);">library_music</span>
        <div class="library-empty-title">YOUR LIBRARY HAS NO ALBUMS YET</div>
        <p class="library-empty-desc">
          Create your own playlist album here, press <strong>":"</strong> beside any song to Like it into <strong>Liked Songs</strong>, or press <strong>"+"</strong> on the music player below.
        </p>
        <button type="button" class="btn-play-all" id="btn-empty-create-playlist">+ CREATE NEW PLAYLIST</button>
      `;
      const btnEmptyCreate = emptyBox.querySelector('#btn-empty-create-playlist');
      if (btnEmptyCreate) {
        btnEmptyCreate.addEventListener('click', promptAndCreatePlaylist);
      }
      DOM.libraryGrid.appendChild(emptyBox);
      return;
    }

    state.playlists.forEach(pl => {
      const liked = isLikedPlaylist(pl);
      const displayName = liked ? LIKED_PLAYLIST_NAME : pl.name;
      const count = Number(pl.track_count || 0);

      const card = document.createElement('article');
      card.className = 'album-card';

      let artInnerHtml = '';
      if (pl.cover_thumb) {
        artInnerHtml = `
          <img class="album-thumb" src="${escapeHtml(pl.cover_thumb)}" alt="${escapeHtml(displayName)}" loading="lazy" style="filter: none;">
        `;
      } else if (liked) {
        artInnerHtml = `
          <div class="library-liked-art">
            <span class="material-symbols-outlined" style="font-size: 46px; font-variation-settings: 'FILL' 1;">favorite</span>
            <span style="font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.08em;">LIKED SONGS</span>
          </div>
        `;
      } else {
        artInnerHtml = `
          <div class="library-default-art">
            <span class="material-symbols-outlined" style="font-size: 44px;">album</span>
            <span style="font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.08em;">USER ALBUM</span>
          </div>
        `;
      }

      card.innerHTML = `
        <div class="album-thumb-wrap">
          ${artInnerHtml}
          ${!liked ? `<button type="button" class="btn-card-delete" title="Delete playlist">✕</button>` : ''}
          <div class="album-play-btn" title="Play ${escapeHtml(displayName)}">
            <span class="material-symbols-outlined" style="font-size: 22px;">play_arrow</span>
          </div>
          <span class="album-badge">${liked ? 'PERMANENT · LIKED' : 'USER ALBUM'}</span>
        </div>
        <div class="album-info">
          <h3 class="album-title" title="${escapeHtml(displayName)}">${escapeHtml(displayName)}</h3>
          <p class="album-artist">${count} ${count === 1 ? 'TRACK' : 'TRACKS'} · ${liked ? 'LIKED SONGS' : 'USER CREATED'}</p>
        </div>
      `;

      // Play entire user album/playlist immediately on ▶ click
      const playBtn = card.querySelector('.album-play-btn');
      if (playBtn) {
        playBtn.addEventListener('click', async (e) => {
          e.stopPropagation();
          const tracks = await fetchPlaylistTracks(pl.id);
          if (tracks.length > 0) {
            playTrack(tracks[0], tracks, { sourceName: displayName });
            showToast(`PLAYING COLLECTION: [${displayName.toUpperCase()}]`);
          } else {
            showToast(`"${displayName.toUpperCase()}" HAS NO TRACKS YET`);
          }
        });
      }

      // Delete button (ONLY present on user-created playlists, NEVER on Liked Songs)
      const delBtn = card.querySelector('.btn-card-delete');
      if (delBtn && !liked) {
        delBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          deletePlaylist(pl.id);
        });
      }

      // Click card -> open playlist detail inside Library
      card.addEventListener('click', () => {
        openPlaylistView(pl);
      });

      DOM.libraryGrid.appendChild(card);
    });
  }

  /**
   * Open a Playlist / User Album Detail View inside Library (without Recommended Master Albums)
   */
  async function openPlaylistView(pl) {
    if (!pl) return;
    const liked = isLikedPlaylist(pl);
    const displayName = liked ? LIKED_PLAYLIST_NAME : pl.name;
    state.activePlaylist = pl;

    setActiveNavHighlight('library');
    switchView('library');
    if (DOM.libraryGridSection) DOM.libraryGridSection.style.display = 'none';
    if (DOM.libraryPlaylistDetail) DOM.libraryPlaylistDetail.style.display = 'block';

    const tracks = await fetchPlaylistTracks(pl.id);
    pl.track_count = tracks.length;
    const latestThumbTrack = [...tracks].reverse().find(t => t && t.thumbnail);
    if (latestThumbTrack) {
      pl.cover_thumb = latestThumbTrack.thumbnail;
    }

    renderSidebarPlaylists();

    // Update Hero Header
    if (DOM.playlistDetailTitle) DOM.playlistDetailTitle.textContent = displayName.toUpperCase();
    if (DOM.playlistDetailType) {
      DOM.playlistDetailType.textContent = liked
        ? 'LIKED SONGS · PERMANENT COLLECTION'
        : 'USER CREATED ALBUM / PLAYLIST';
    }
    if (DOM.playlistDetailBadge) {
      DOM.playlistDetailBadge.textContent = liked ? 'LIKED SONGS' : 'USER ALBUM';
    }
    if (DOM.playlistDetailModuleTag) {
      DOM.playlistDetailModuleTag.textContent = liked
        ? 'SYSTEM ARCHIVE: LIKED_SONGS [PROTECTED]'
        : 'USER LIBRARY: CUSTOM_ALBUM';
    }
    if (DOM.playlistDetailDesc) {
      DOM.playlistDetailDesc.textContent = liked
        ? `Songs you liked across APOLLO (${tracks.length} ${tracks.length === 1 ? 'track' : 'tracks'}). This collection is permanent and cannot be deleted.`
        : `${tracks.length} ${tracks.length === 1 ? 'track' : 'tracks'} saved in "${displayName}".`;
    }
    if (DOM.playlistDetailCover) {
      DOM.playlistDetailCover.src = pl.cover_thumb || DEFAULT_FALLBACK_THUMB;
      DOM.playlistDetailCover.style.filter = 'none';
    }
    if (DOM.playlistDetailCountBadge) {
      DOM.playlistDetailCountBadge.textContent = `${tracks.length} ${tracks.length === 1 ? 'TRACK' : 'TRACKS'}`;
    }
    if (DOM.playlistTracksHeading) {
      DOM.playlistTracksHeading.textContent = `${displayName.toUpperCase()} · TRACKLIST`;
    }
    if (DOM.playlistTracksMeta) {
      DOM.playlistTracksMeta.textContent = `${tracks.length} ${tracks.length === 1 ? 'TRACK' : 'TRACKS'}`;
    }

    // Strict protection: Hide delete button on "Liked Songs"
    if (DOM.btnDeletePlaylist) {
      if (liked) {
        DOM.btnDeletePlaylist.style.display = 'none';
        DOM.btnDeletePlaylist.onclick = null;
      } else {
        DOM.btnDeletePlaylist.style.display = 'inline-flex';
        DOM.btnDeletePlaylist.onclick = () => deletePlaylist(pl.id);
      }
    }

    if (DOM.btnPlayAllPlaylist) {
      DOM.btnPlayAllPlaylist.onclick = () => {
        if (tracks.length > 0) {
          playTrack(tracks[0], tracks, { sourceName: displayName });
        } else {
          showToast('NO TRACKS IN THIS PLAYLIST YET');
        }
      };
    }

    renderPlaylistDetailTracks(tracks, pl);
  }

  function renderPlaylistDetailTracks(tracks, playlistObj) {
    if (!DOM.playlistTracksBody) return;
    DOM.playlistTracksBody.innerHTML = '';

    if (!tracks || tracks.length === 0) {
      DOM.playlistTracksBody.innerHTML = `
        <tr>
          <td colspan="5" style="text-align: center; padding: 36px 18px; font-family: var(--font-mono); color: var(--text-dim); font-size: 11px;">
            NO TRACKS SAVED YET · USE ":" BESIDE ANY SONG OR "+" ON THE PLAYER BELOW TO ADD MUSIC
          </td>
        </tr>
      `;
      return;
    }

    tracks.forEach((track, index) => {
      const tr = document.createElement('tr');
      tr.dataset.videoId = track.video_id;
      if (state.currentTrack && state.currentTrack.video_id === track.video_id) {
        tr.classList.add('playing');
      }

      const numStr = String(index + 1).padStart(2, '0');
      tr.innerHTML = `
        <td class="col-num">
          <span class="row-index">${numStr}</span>
          <span class="material-symbols-outlined row-play-icon">play_arrow</span>
        </td>
        <td class="col-title">
          <div class="track-title-cell">
            <img class="track-inline-thumb" src="${escapeHtml(track.thumbnail || DEFAULT_FALLBACK_THUMB)}" alt="">
            <div>
              <span class="track-title-text">${escapeHtml(track.title)}</span>
              <div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-dim);" class="sm-artist-mobile">
                ${escapeHtml(track.artist)}
              </div>
            </div>
          </div>
        </td>
        <td class="col-artist">
          <span class="artist-click-link">${escapeHtml(track.artist)}</span>
        </td>
        <td class="col-dur">${escapeHtml(track.duration || '03:30')}</td>
        <td class="col-action">
          <button type="button" class="btn-add-popover" title="Track options">:</button>
        </td>
      `;

      tr.addEventListener('click', (e) => {
        if (e.target.closest('.btn-add-popover') || e.target.closest('.artist-click-link')) return;
        const plTitle = playlistObj ? (isLikedPlaylist(playlistObj) ? LIKED_PLAYLIST_NAME : playlistObj.name) : 'Playlist';
        playTrack(track, tracks, { sourceName: plTitle });
      });

      const artLink = tr.querySelector('.artist-click-link');
      if (artLink) {
        artLink.addEventListener('click', (e) => {
          e.stopPropagation();
          openArtistView(track.artist);
        });
      }

      const optBtn = tr.querySelector('.btn-add-popover');
      if (optBtn) {
        optBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          openPlaylistPopover(e, track, { mode: 'track_menu', contextPlaylist: playlistObj });
        });
      }

      DOM.playlistTracksBody.appendChild(tr);
    });
  }

  /**
   * Queue Drawer Management ("Now playing", "Next in queue", "Next from: ...")
   */
  function toggleQueueDrawer(forceState = null) {
    if (!DOM.queueDrawer) return;
    state.isQueueOpen = (forceState !== null) ? Boolean(forceState) : !state.isQueueOpen;
    DOM.queueDrawer.classList.toggle('open', state.isQueueOpen);
    if (DOM.queueToggleBtn) {
      DOM.queueToggleBtn.classList.toggle('active', state.isQueueOpen);
    }
    if (state.isQueueOpen) {
      renderQueueDrawer();
    }
  }

  function addTrackToQueue(track) {
    if (!track || !track.video_id) return;

    // If no song has even been loaded yet, set and play immediately
    if (!state.currentTrack) {
      playTrack(track, [track], { sourceName: 'Queue' });
      return;
    }

    state.manualQueue.push({
      ...track,
      _queueUid: `${Date.now()}_${Math.random().toString(36).slice(2, 7)}`
    });

    // Re-prime background preloader so the newly queued song is buffered next
    state.preloadedTrack = null;
    state.preloadedStreamUrl = null;
    preloadNextTrackInQueue();

    renderQueueDrawer();
    showToast(`ADDED TO QUEUE: "${track.title.toUpperCase()}"`);
  }

  function renderQueueDrawer() {
    if (!DOM.queueDrawer) return;

    // 1. Now Playing
    if (DOM.queueNowPlaying) {
      DOM.queueNowPlaying.innerHTML = '';
      if (state.currentTrack) {
        const nowEl = document.createElement('div');
        nowEl.className = 'queue-track-item';
        nowEl.innerHTML = `
          <img class="queue-track-thumb" src="${escapeHtml(state.currentTrack.thumbnail || DEFAULT_FALLBACK_THUMB)}" alt="">
          <div class="queue-track-meta">
            <div class="queue-track-title now-active">${escapeHtml(state.currentTrack.title)}</div>
            <div class="queue-track-artist">${escapeHtml(state.currentTrack.artist || '')}</div>
          </div>
        `;
        nowEl.addEventListener('click', togglePlayPause);
        DOM.queueNowPlaying.appendChild(nowEl);
      } else {
        DOM.queueNowPlaying.innerHTML = `<div class="queue-empty-note">No track currently active</div>`;
      }
    }

    // 2. Next in Queue (Manually added via "Add to queue")
    if (DOM.queueManualSection && DOM.queueManualList) {
      DOM.queueManualList.innerHTML = '';
      if (state.manualQueue.length > 0) {
        DOM.queueManualSection.style.display = 'flex';
        state.manualQueue.forEach((mTrack, idx) => {
          const row = document.createElement('div');
          row.className = 'queue-track-item';
          row.innerHTML = `
            <img class="queue-track-thumb" src="${escapeHtml(mTrack.thumbnail || DEFAULT_FALLBACK_THUMB)}" alt="">
            <div class="queue-track-meta">
              <div class="queue-track-title">${escapeHtml(mTrack.title)}</div>
              <div class="queue-track-artist">${escapeHtml(mTrack.artist || '')}</div>
            </div>
            <button type="button" class="btn-queue-remove" title="Remove from queue">
              <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
            </button>
          `;
          row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-queue-remove')) return;
            state.manualQueue.splice(idx, 1);
            playTrack(mTrack, null, { fromManualQueue: true });
          });
          const rmBtn = row.querySelector('.btn-queue-remove');
          if (rmBtn) {
            rmBtn.addEventListener('click', (e) => {
              e.stopPropagation();
              state.manualQueue.splice(idx, 1);
              renderQueueDrawer();
            });
          }
          DOM.queueManualList.appendChild(row);
        });
      } else {
        DOM.queueManualSection.style.display = 'none';
      }
    }

    // 3. Next from: <Source Name>
    if (DOM.queueNextFromTitle) {
      DOM.queueNextFromTitle.textContent = `Next from: ${state.queueSourceName || 'YouTube Music'}`;
    }

    if (DOM.queueUpcomingList) {
      DOM.queueUpcomingList.innerHTML = '';
      const startIdx = Math.max(0, state.queueIndex + 1);
      const upcoming = state.queue.slice(startIdx);

      if (upcoming.length === 0) {
        DOM.queueUpcomingList.innerHTML = `<div class="queue-empty-note">End of queue — add songs with ":" → Add to queue</div>`;
      } else {
        upcoming.forEach((uTrack, offset) => {
          const realQueueIndex = startIdx + offset;
          const row = document.createElement('div');
          row.className = 'queue-track-item';
          row.innerHTML = `
            <img class="queue-track-thumb" src="${escapeHtml(uTrack.thumbnail || DEFAULT_FALLBACK_THUMB)}" alt="">
            <div class="queue-track-meta">
              <div class="queue-track-title">${escapeHtml(uTrack.title)}</div>
              <div class="queue-track-artist">${escapeHtml(uTrack.artist || '')}</div>
            </div>
            <button type="button" class="btn-queue-remove" title="Remove from queue">
              <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
            </button>
          `;
          row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-queue-remove')) return;
            state.queueIndex = realQueueIndex;
            playTrack(uTrack);
          });
          const rmBtn = row.querySelector('.btn-queue-remove');
          if (rmBtn) {
            rmBtn.addEventListener('click', (e) => {
              e.stopPropagation();
              state.queue.splice(realQueueIndex, 1);
              renderQueueDrawer();
            });
          }
          DOM.queueUpcomingList.appendChild(row);
        });
      }
    }
  }

  /**
   * Headless YouTube Audio Bridge (Automatic Cloud/Vercel Fallback when direct stream is blocked)
   */
  function ensureYtBridgeLoaded() {
    if (window.YT && window.YT.Player) {
      initYtBridgePlayer();
      return;
    }
    if (!document.getElementById('apollo-yt-iframe-api')) {
      const prevCallback = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = function () {
        if (typeof prevCallback === 'function') prevCallback();
        initYtBridgePlayer();
      };
      const tag = document.createElement('script');
      tag.id = 'apollo-yt-iframe-api';
      tag.src = 'https://www.youtube.com/iframe_api';
      document.head.appendChild(tag);
    }
  }

  function initYtBridgePlayer() {
    if (state.ytPlayer || !(window.YT && window.YT.Player)) return;
    const targetEl = document.getElementById('apollo-yt-bridge');
    if (!targetEl) return;

    state.ytPlayer = new window.YT.Player('apollo-yt-bridge', {
      height: '1',
      width: '1',
      videoId: state.ytPendingVideoId || '',
      playerVars: {
        autoplay: 1,
        controls: 0,
        disablekb: 1,
        fs: 0,
        modestbranding: 1,
        playsinline: 1,
        rel: 0
      },
      events: {
        onReady: (ev) => {
          state.ytApiReady = true;
          ev.target.setVolume(state.isMuted ? 0 : Math.round(state.volume * 100));
          if (state.ytPendingVideoId) {
            const vid = state.ytPendingVideoId;
            state.ytPendingVideoId = null;
            ev.target.loadVideoById(vid);
            ev.target.playVideo();
          }
        },
        onStateChange: (ev) => {
          if (state.playbackBackend !== 'yt_bridge') return;
          const YTState = window.YT.PlayerState;
          if (ev.data === YTState.PLAYING) {
            state.isPlaying = true;
            updatePlayPauseButton();
            updateActiveTrackRowHighlight();
            updateMediaSessionPlaybackState();
            startYtProgressLoop();
          } else if (ev.data === YTState.PAUSED) {
            state.isPlaying = false;
            updatePlayPauseButton();
            updateMediaSessionPlaybackState();
          } else if (ev.data === YTState.ENDED) {
            stopYtProgressLoop();
            if (state.repeatMode === 2 && state.ytPlayer) {
              state.ytPlayer.seekTo(0, true);
              state.ytPlayer.playVideo();
            } else {
              playNextTrack();
            }
          }
        },
        onError: async (ev) => {
          if (state.playbackBackend !== 'yt_bridge' || !state.currentTrack) return;
          // Error 101/150 means specific Topic video restricts embed -> auto-resolve playable audio match!
          const curTrack = state.currentTrack;
          if (state.ytFallbackTriedForId !== curTrack.video_id) {
            state.ytFallbackTriedForId = curTrack.video_id;
            try {
              const q = `${curTrack.artist || ''} ${curTrack.title || ''} audio`.trim();
              const sRes = await fetch(`api/search.php?q=${encodeURIComponent(q)}`);
              const sData = await sRes.json();
              if (sData.status === 'ok' && Array.isArray(sData.tracks)) {
                const alt = sData.tracks.find(t => t.video_id && t.video_id !== curTrack.video_id && t.source === 'youtube')
                  || sData.tracks.find(t => t.video_id && t.video_id !== curTrack.video_id);
                if (alt && state.ytPlayer && state.ytApiReady) {
                  state.ytPlayer.loadVideoById(alt.video_id);
                  state.ytPlayer.playVideo();
                  return;
                }
              }
            } catch (err) {}
          }
          showToast('TRACK RESTRICTED BY LABEL — SKIPPING TO NEXT');
          setTimeout(() => playNextTrack(), 900);
        }
      }
    });
  }

  function startYtProgressLoop() {
    clearInterval(state.ytProgressTimer);
    state.ytProgressTimer = setInterval(() => {
      if (state.playbackBackend !== 'yt_bridge' || !state.ytPlayer || !state.ytApiReady) return;
      try {
        const cur = state.ytPlayer.getCurrentTime() || 0;
        const dur = state.ytPlayer.getDuration() || 0;
        DOM.timeCurrent.textContent = formatTime(cur);
        if (dur > 0) {
          DOM.timeDuration.textContent = formatTime(dur);
          DOM.seekFill.style.width = `${Math.min(100, (cur / dur) * 100)}%`;
        }
      } catch (e) {}
    }, 250);
  }

  function stopYtProgressLoop() {
    clearInterval(state.ytProgressTimer);
  }

  function playViaYtBridge(track) {
    try { state.audio.pause(); } catch (e) {}
    state.audio.removeAttribute('src');
    state.playbackBackend = 'yt_bridge';
    ensureYtBridgeLoaded();

    if (state.ytPlayer && state.ytApiReady && typeof state.ytPlayer.loadVideoById === 'function') {
      state.ytPendingVideoId = null;
      state.ytPlayer.setVolume(state.isMuted ? 0 : Math.round(state.volume * 100));
      state.ytPlayer.loadVideoById(track.video_id);
      state.ytPlayer.playVideo();
    } else {
      state.ytPendingVideoId = track.video_id;
    }

    state.isPlaying = true;
    updatePlayPauseButton();
    updateActiveTrackRowHighlight();
    updateMediaSessionPlaybackState();
    showToast(`NOW PLAYING: ${track.title.toUpperCase()}`);
  }

  /**
   * Audio Engine Playback Logic (Dual-Engine: Direct PHP Stream + Seamless YT Bridge Fallback)
   */
  async function playTrack(track, queueList = null, playOpts = {}) {
    if (!track || !track.video_id) return;

    if (playOpts.sourceName) {
      state.queueSourceName = playOpts.sourceName;
    }

    if (!playOpts.fromManualQueue) {
      if (queueList && Array.isArray(queueList)) {
        state.queue = [...queueList];
        state.queueIndex = state.queue.findIndex(t => t.video_id === track.video_id);
        if (state.queueIndex === -1) {
          state.queue.unshift(track);
          state.queueIndex = 0;
        }
      } else if (state.queue.length > 0) {
        const idx = state.queue.findIndex(t => t.video_id === track.video_id);
        if (idx !== -1) {
          state.queueIndex = idx;
        } else {
          const nextIdx = state.queueIndex + 1;
          state.queue.splice(nextIdx, 0, track);
          state.queueIndex = nextIdx;
        }
      } else {
        state.queue = [track];
        state.queueIndex = 0;
      }
    }

    state.currentTrack = track;
    setNowPlayingInfo(track);
    saveRecentTrack(track);
    renderQueueDrawer();

    // Record song playback to MySQL activity_logs asynchronously
    fetch('api/activity.php?action=log_play', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        video_id: track.video_id,
        title: track.title || '',
        artist: track.artist || '',
        album: track.album || ''
      })
    }).catch(() => {});

    // Stop YT bridge if previously active before attempting direct HTML5 stream
    if (state.ytPlayer && state.ytApiReady && typeof state.ytPlayer.stopVideo === 'function') {
      try { state.ytPlayer.stopVideo(); } catch (e) {}
    }
    stopYtProgressLoop();
    state.playbackBackend = 'html5';
    state.audio.loop = (state.repeatMode === 2);

    // Cancel any previous in-flight stream resolution so rapid clicks never trigger false errors
    state.playRequestSeq = (state.playRequestSeq || 0) + 1;
    const reqSeq = state.playRequestSeq;
    if (state.playAbortController) {
      try { state.playAbortController.abort(); } catch (e) {}
    }
    state.playAbortController = new AbortController();

    // 1. Check if track was already preloaded in background
    if (state.preloadedTrack && state.preloadedTrack.video_id === track.video_id && state.preloadedStreamUrl) {
      const audioUrl = state.preloadedStreamUrl;
      state.preloadedTrack = null;
      state.preloadedStreamUrl = null;

      state.audio.src = audioUrl;
      try {
        await state.audio.play();
        if (reqSeq !== state.playRequestSeq) return;
        state.isPlaying = true;
        updatePlayPauseButton();
        updateActiveTrackRowHighlight();
        updateMediaSessionPlaybackState();
        showToast(`NOW PLAYING: ${track.title.toUpperCase()}`);
        return;
      } catch (e) {
        if (e && e.name === 'AbortError') return;
        console.warn('Preloaded stream play failed, falling back to fresh stream fetch:', e);
      }
    }

    showToast(`LOADING AUDIO: ${track.title.toUpperCase()}`);

    try {
      const res = await fetch(`api/stream.php?v=${encodeURIComponent(track.video_id)}`, {
        signal: state.playAbortController.signal
      });
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }
      const data = await res.json();
      if (reqSeq !== state.playRequestSeq) return;

      if (data.status === 'use_yt_bridge') {
        playViaYtBridge(track);
        return;
      }

      if (data.status === 'ok') {
        const primaryUrl = data.proxy_url || data.stream_url;
        state.audio.src = primaryUrl;

        try {
          await state.audio.play();
          if (reqSeq !== state.playRequestSeq) return;
          state.isPlaying = true;
          updatePlayPauseButton();
          updateActiveTrackRowHighlight();
          updateMediaSessionPlaybackState();
          return;
        } catch (playErr) {
          if (playErr && playErr.name === 'AbortError') return;
          console.warn('Direct stream play blocked, engaging YT Audio Bridge:', playErr);
          playViaYtBridge(track);
          return;
        }
      } else {
        playViaYtBridge(track);
      }
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      if (reqSeq !== state.playRequestSeq) return;
      console.warn('Direct stream endpoint unavailable, engaging YT Audio Bridge:', err);
      playViaYtBridge(track);
    }
  }

  function getUpcomingTrack() {
    if (state.manualQueue.length > 0) {
      return state.manualQueue[0];
    }
    if (state.queue.length === 0) return null;
    if (state.repeatMode === 2) {
      return state.currentTrack;
    }
    if (state.isShuffle) {
      const candidates = state.queue.filter(t => !state.currentTrack || t.video_id !== state.currentTrack.video_id);
      return candidates.length > 0 ? candidates[Math.floor(Math.random() * candidates.length)] : state.queue[0];
    }
    if (state.queueIndex < state.queue.length - 1) {
      return state.queue[state.queueIndex + 1];
    }
    if (state.repeatMode === 1 && state.queue.length > 0) {
      return state.queue[0];
    }
    return null;
  }

  async function preloadNextTrackInQueue() {
    if (state.isPreloading) return;
    const nextTrack = getUpcomingTrack();
    if (!nextTrack || !nextTrack.video_id) return;

    if (state.preloadedTrack && state.preloadedTrack.video_id === nextTrack.video_id && state.preloadedStreamUrl) {
      return;
    }

    state.isPreloading = true;
    try {
      const res = await fetch(`api/stream.php?v=${encodeURIComponent(nextTrack.video_id)}`);
      const data = await res.json();
      if (data.status === 'ok') {
        const streamUrl = data.proxy_url || data.stream_url;
        state.preloadedTrack = nextTrack;
        state.preloadedStreamUrl = streamUrl;
      }
    } catch (err) {
      console.warn('[APOLLO PRELOAD] Failed to prime next track:', err);
    } finally {
      state.isPreloading = false;
    }
  }

  let stallWatchdogTimer = null;

  async function recoverAudioStream(reason = 'stalled') {
    if (state.isRecoveringAudio || !state.currentTrack) return;
    state.isRecoveringAudio = true;
    const resumePos = state.audio.currentTime || 0;
    console.warn(`[APOLLO AUDIO RECOVERY] Triggered (${reason}) at ${resumePos.toFixed(1)}s`);
    showToast(`RE-ENGAGING AUDIO SIGNAL (${formatTime(resumePos)})...`);

    try {
      const res = await fetch(`api/stream.php?v=${encodeURIComponent(state.currentTrack.video_id)}&refresh=1`);
      const data = await res.json();
      if (data.status === 'ok') {
        const freshUrl = `${data.proxy_url || data.stream_url}&t=${Date.now()}`;
        state.audio.src = freshUrl;
        state.audio.load();

        const onReadyToResume = async () => {
          state.audio.removeEventListener('canplay', onReadyToResume);
          try {
            if (resumePos > 0) {
              state.audio.currentTime = resumePos;
            }
            await state.audio.play();
            state.isPlaying = true;
            updatePlayPauseButton();
            showToast(`AUDIO STREAM RESTORED: ${state.currentTrack.title.toUpperCase()}`);
          } catch (err) {
            console.warn('Resume play failed:', err);
          } finally {
            state.isRecoveringAudio = false;
          }
        };

        state.audio.addEventListener('canplay', onReadyToResume, { once: true });
      } else {
        state.isRecoveringAudio = false;
      }
    } catch (e) {
      console.error('Audio stream recovery failed:', e);
      state.isRecoveringAudio = false;
    }
  }

  function togglePlayPause() {
    if (!state.currentTrack) {
      if (liveRecommendedTracks.length > 0) {
        playTrack(liveRecommendedTracks[0], liveRecommendedTracks);
      }
      return;
    }

    if (state.playbackBackend === 'yt_bridge' && state.ytPlayer && state.ytApiReady) {
      const pState = state.ytPlayer.getPlayerState();
      if (pState === window.YT.PlayerState.PLAYING) {
        state.ytPlayer.pauseVideo();
        state.isPlaying = false;
      } else {
        state.ytPlayer.playVideo();
        state.isPlaying = true;
      }
      updatePlayPauseButton();
      updateMediaSessionPlaybackState();
      return;
    }

    // If audio src has not been loaded yet (initial default track), fetch & play immediately
    if (!state.audio.src) {
      playTrack(state.currentTrack, state.queue.length > 0 ? state.queue : liveRecommendedTracks);
      return;
    }

    if (state.audio.paused) {
      state.audio.play().then(() => {
        state.isPlaying = true;
        updatePlayPauseButton();
        updateMediaSessionPlaybackState();
      }).catch(err => {
        console.warn('Play attempt failed, initiating stream recovery:', err);
        recoverAudioStream('user-play-failed');
      });
    } else {
      state.audio.pause();
      state.isPlaying = false;
      updatePlayPauseButton();
      updateMediaSessionPlaybackState();
    }
  }

  function playNextTrack() {
    // 1. Play manually queued tracks ("Add to queue") first in FIFO order
    if (state.manualQueue.length > 0) {
      const nextManual = state.manualQueue.shift();
      playTrack(nextManual, null, { fromManualQueue: true });
      return;
    }

    if (state.queue.length === 0) return;

    if (state.isShuffle) {
      const randomIndex = Math.floor(Math.random() * state.queue.length);
      state.queueIndex = randomIndex;
      playTrack(state.queue[randomIndex]);
      return;
    }

    if (state.queueIndex < state.queue.length - 1) {
      state.queueIndex++;
      playTrack(state.queue[state.queueIndex]);
    } else if (state.repeatMode === 1) {
      state.queueIndex = 0;
      playTrack(state.queue[0]);
    } else {
      state.isPlaying = false;
      updatePlayPauseButton();
    }
  }

  function playPreviousTrack() {
    if (state.playbackBackend === 'yt_bridge' && state.ytPlayer && state.ytApiReady) {
      if ((state.ytPlayer.getCurrentTime() || 0) > 3) {
        state.ytPlayer.seekTo(0, true);
        return;
      }
    } else if (state.audio.currentTime > 3) {
      state.audio.currentTime = 0;
      return;
    }
    if (state.queue.length === 0) return;

    if (state.queueIndex > 0) {
      state.queueIndex--;
      playTrack(state.queue[state.queueIndex]);
    } else {
      state.queueIndex = state.queue.length - 1;
      playTrack(state.queue[state.queueIndex]);
    }
  }

  function cycleRepeatMode() {
    state.repeatMode = (state.repeatMode + 1) % 3;
    DOM.repeatBtn.classList.remove('mode-1', 'mode-2');

    if (state.repeatMode === 0) {
      state.audio.loop = false;
      DOM.repeatBtn.title = 'Repeat Off';
      showToast('AUDIO ENGINE: REPEAT OFF');
    } else if (state.repeatMode === 1) {
      state.audio.loop = false;
      DOM.repeatBtn.classList.add('mode-1');
      DOM.repeatBtn.title = 'Repeat All (Queue Loop)';
      showToast('AUDIO ENGINE: REPEAT ALL ACTIVE');
    } else if (state.repeatMode === 2) {
      state.audio.loop = true;
      DOM.repeatBtn.classList.add('mode-2');
      DOM.repeatBtn.title = 'Repeat One (Single Signal Loop)';
      showToast('AUDIO ENGINE: REPEAT ONE [1] ACTIVE');
    }
  }

  function toggleShuffle() {
    state.isShuffle = !state.isShuffle;
    if (state.isShuffle) {
      DOM.shuffleBtn.classList.add('active');
      showToast('AUDIO ENGINE: SHUFFLE ACTIVE');
    } else {
      DOM.shuffleBtn.classList.remove('active');
      showToast('AUDIO ENGINE: SHUFFLE DISABLED');
    }
  }

  /**
   * UI Updates
   */
  function updatePlayPauseButton() {
    if (state.isPlaying) {
      DOM.playPauseIcon.textContent = 'pause';
      DOM.playPauseBtn.title = 'Pause';
    } else {
      DOM.playPauseIcon.textContent = 'play_arrow';
      DOM.playPauseBtn.title = 'Play';
    }
  }

  function setNowPlayingInfo(track) {
    if (!track) return;
    DOM.nowTitle.textContent = track.title;
    DOM.nowArtist.textContent = track.artist;
    DOM.nowThumb.referrerPolicy = 'no-referrer';
    DOM.nowThumb.onerror = () => {
      if (track.video_id && !DOM.nowThumb.src.includes('i.ytimg.com')) {
        DOM.nowThumb.src = `https://i.ytimg.com/vi/${encodeURIComponent(track.video_id)}/hqdefault.jpg`;
      } else {
        DOM.nowThumb.src = DEFAULT_FALLBACK_THUMB;
      }
    };
    DOM.nowThumb.src = track.thumbnail || DEFAULT_FALLBACK_THUMB;
    DOM.nowThumb.style.filter = 'none';
    DOM.nowThumb.alt = track.title;
  }

  function updateActiveTrackRowHighlight() {
    document.querySelectorAll('.tracks-table tbody tr').forEach(row => {
      if (row.dataset.videoId === (state.currentTrack ? state.currentTrack.video_id : '')) {
        row.classList.add('playing');
      } else {
        row.classList.remove('playing');
      }
    });
  }

  /**
   * Audio Element Event Handlers
   */
  function setupAudioListeners() {
    state.audio.addEventListener('timeupdate', () => {
      const cur = state.audio.currentTime || 0;
      const dur = state.audio.duration || 0;
      DOM.timeCurrent.textContent = formatTime(cur);
      if (dur > 0) {
        DOM.timeDuration.textContent = formatTime(dur);
        const percent = (cur / dur) * 100;
        DOM.seekFill.style.width = percent + '%';

        if (dur >= 25 && (dur - cur <= 25 || cur / dur >= 0.85)) {
          preloadNextTrackInQueue();
        }
      }
    });

    state.audio.addEventListener('ended', () => {
      if (state.repeatMode === 2) {
        state.audio.currentTime = 0;
        state.audio.play().catch(console.warn);
      } else {
        playNextTrack();
      }
    });

    state.audio.addEventListener('error', (e) => {
      if (state.playbackBackend !== 'html5' || !state.audio.getAttribute('src')) return;
      console.warn('Audio element error event:', e);
      if (state.currentTrack && state.isPlaying) {
        recoverAudioStream('error');
      }
    });

    state.audio.addEventListener('waiting', () => {
      clearTimeout(stallWatchdogTimer);
      if (state.isPlaying && !state.audio.paused) {
        stallWatchdogTimer = setTimeout(() => {
          if (state.isPlaying && state.audio.readyState < 3) {
            recoverAudioStream('buffer-timeout');
          }
        }, 5000);
      }
    });

    state.audio.addEventListener('playing', () => {
      clearTimeout(stallWatchdogTimer);
      state.isPlaying = true;
      updatePlayPauseButton();
    });

    state.audio.addEventListener('pause', () => {
      if (!state.isRecoveringAudio) {
        state.isPlaying = false;
        updatePlayPauseButton();
      }
    });

    DOM.seekBar.addEventListener('click', (e) => {
      const rect = DOM.seekBar.getBoundingClientRect();
      const clickX = e.clientX - rect.left;
      const width = rect.width;
      const percent = Math.max(0, Math.min(1, clickX / width));
      if (state.playbackBackend === 'yt_bridge' && state.ytPlayer && state.ytApiReady) {
        const dur = state.ytPlayer.getDuration() || 0;
        if (dur > 0) {
          state.ytPlayer.seekTo(percent * dur, true);
          DOM.seekFill.style.width = `${percent * 100}%`;
        }
        return;
      }
      if (state.audio.duration) {
        state.audio.currentTime = percent * state.audio.duration;
      }
    });

    DOM.volBar.addEventListener('click', (e) => {
      const rect = DOM.volBar.getBoundingClientRect();
      const clickX = e.clientX - rect.left;
      const width = rect.width;
      const percent = Math.max(0, Math.min(1, clickX / width));
      setVolume(percent);
    });

    DOM.volBtn.addEventListener('click', () => {
      if (state.isMuted) {
        state.isMuted = false;
        state.audio.volume = state.volume;
        if (state.ytPlayer && state.ytApiReady) {
          state.ytPlayer.unMute();
          state.ytPlayer.setVolume(Math.round(state.volume * 100));
        }
        DOM.volFill.style.width = (state.volume * 100) + '%';
        DOM.volIcon.textContent = state.volume > 0.5 ? 'volume_up' : 'volume_down';
      } else {
        state.isMuted = true;
        state.audio.volume = 0;
        if (state.ytPlayer && state.ytApiReady) {
          state.ytPlayer.mute();
        }
        DOM.volFill.style.width = '0%';
        DOM.volIcon.textContent = 'volume_off';
      }
    });
  }

  function setVolume(vol) {
    state.volume = vol;
    state.isMuted = (vol === 0);
    state.audio.volume = vol;
    if (state.ytPlayer && state.ytApiReady) {
      if (vol === 0) {
        state.ytPlayer.mute();
      } else {
        state.ytPlayer.unMute();
        state.ytPlayer.setVolume(Math.round(vol * 100));
      }
    }
    DOM.volFill.style.width = (vol * 100) + '%';
    if (vol === 0) {
      DOM.volIcon.textContent = 'volume_off';
    } else if (vol < 0.5) {
      DOM.volIcon.textContent = 'volume_down';
    } else {
      DOM.volIcon.textContent = 'volume_up';
    }
  }

  function promptAndCreatePlaylist() {
    const name = prompt('Enter new playlist / album name:');
    if (name && name.trim()) {
      createPlaylist(name.trim());
    }
  }

  /**
   * User Authentication Helpers (Login / Register / Logout)
   */
  function updateHeaderAuthUI() {
    if (state.currentUser && state.currentUser.username) {
      if (DOM.authGuestBox) DOM.authGuestBox.style.display = 'none';
      if (DOM.authUserBox) DOM.authUserBox.style.display = 'inline-flex';
      if (DOM.headerUsername) DOM.headerUsername.textContent = state.currentUser.username;
    } else {
      if (DOM.authGuestBox) DOM.authGuestBox.style.display = 'inline-flex';
      if (DOM.authUserBox) DOM.authUserBox.style.display = 'none';
    }
  }

  function showAuthAlert(msg) {
    if (!DOM.authAlert) return;
    if (!msg) {
      DOM.authAlert.style.display = 'none';
      DOM.authAlert.textContent = '';
      return;
    }
    DOM.authAlert.textContent = msg;
    DOM.authAlert.style.display = 'block';
  }

  function switchAuthTab(tab) {
    showAuthAlert('');
    if (tab === 'register') {
      if (DOM.tabAuthLogin) DOM.tabAuthLogin.classList.remove('active');
      if (DOM.tabAuthRegister) DOM.tabAuthRegister.classList.add('active');
      if (DOM.formAuthLogin) DOM.formAuthLogin.style.display = 'none';
      if (DOM.formAuthRegister) DOM.formAuthRegister.style.display = 'flex';
      if (DOM.regUsername) setTimeout(() => DOM.regUsername.focus(), 50);
    } else {
      if (DOM.tabAuthRegister) DOM.tabAuthRegister.classList.remove('active');
      if (DOM.tabAuthLogin) DOM.tabAuthLogin.classList.add('active');
      if (DOM.formAuthRegister) DOM.formAuthRegister.style.display = 'none';
      if (DOM.formAuthLogin) DOM.formAuthLogin.style.display = 'flex';
      if (DOM.loginIdentity) setTimeout(() => DOM.loginIdentity.focus(), 50);
    }
  }

  function openAuthModal(defaultTab = 'login') {
    if (!DOM.authModalOverlay) return;
    DOM.authModalOverlay.style.display = 'flex';
    switchAuthTab(defaultTab);
  }

  function closeAuthModal() {
    if (!DOM.authModalOverlay) return;
    DOM.authModalOverlay.style.display = 'none';
    showAuthAlert('');
  }

  /**
   * UI Setup & General Listeners
   */
  function setupUIListeners() {
    DOM.playPauseBtn.addEventListener('click', togglePlayPause);
    DOM.nextBtn.addEventListener('click', playNextTrack);
    DOM.prevBtn.addEventListener('click', playPreviousTrack);
    DOM.repeatBtn.addEventListener('click', cycleRepeatMode);
    DOM.shuffleBtn.addEventListener('click', toggleShuffle);

    // Now Playing Artist click
    DOM.nowArtist.addEventListener('click', () => {
      if (state.currentTrack && state.currentTrack.artist) {
        openArtistView(state.currentTrack.artist);
      }
    });

    // Bottom Music Player "+" Button -> Add currently loaded/playing song to playlist
    if (DOM.nowAddBtn) {
      DOM.nowAddBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const activeSong = state.currentTrack || state.queue[0] || liveRecommendedTracks[0];
        if (!activeSong) {
          showToast('NO TRACK SELECTED');
          return;
        }
        state.currentTrack = activeSong;
        openPlaylistPopover(e, activeSong, { mode: 'player_add' });
      });
    }

    // Sidebar & Library "+ NEW PLAYLIST" Buttons
    if (DOM.newPlaylistBtn) {
      DOM.newPlaylistBtn.addEventListener('click', promptAndCreatePlaylist);
    }
    if (DOM.btnLibraryNewPlaylist) {
      DOM.btnLibraryNewPlaylist.addEventListener('click', promptAndCreatePlaylist);
    }
    if (DOM.btnRefreshCommunity) {
      DOM.btnRefreshCommunity.addEventListener('click', () => {
        loadCommunityPlaylists(true);
      });
    }

    // Back buttons
    if (DOM.btnBackOverview) {
      DOM.btnBackOverview.addEventListener('click', () => {
        handleNavigation('home');
      });
    }
    if (DOM.btnBackLibrary) {
      DOM.btnBackLibrary.addEventListener('click', () => {
        openLibraryHome();
      });
    }

    // Search Inputs: Immediate on Enter key, 600ms debounce on typing
    const handleSearchKeydown = (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(state.searchDebounceTimer);
        const q = e.target.value.trim();
        if (q) {
          switchView('overview');
        }
        performSearch(q);
      }
    };

    const handleSearchInput = (e) => {
      const val = e.target.value;
      const q = val.trim();
      if (DOM.searchInput && e.target !== DOM.searchInput) DOM.searchInput.value = val;
      if (DOM.exploreSearchInput && e.target !== DOM.exploreSearchInput) DOM.exploreSearchInput.value = val;

      clearTimeout(state.searchDebounceTimer);
      if (!q) {
        performSearch('');
        return;
      }

      state.searchDebounceTimer = setTimeout(() => {
        switchView('overview');
        performSearch(q);
      }, 600);
    };

    if (DOM.searchInput) {
      DOM.searchInput.addEventListener('input', handleSearchInput);
      DOM.searchInput.addEventListener('keydown', handleSearchKeydown);
    }
    if (DOM.exploreSearchInput) {
      DOM.exploreSearchInput.addEventListener('input', handleSearchInput);
      DOM.exploreSearchInput.addEventListener('keydown', handleSearchKeydown);
    }

    // Keyboard ESC handler
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (DOM.searchInput === document.activeElement || (DOM.exploreSearchInput && DOM.exploreSearchInput === document.activeElement)) {
          if (DOM.searchInput) DOM.searchInput.value = '';
          if (DOM.exploreSearchInput) DOM.exploreSearchInput.value = '';
          if (DOM.searchInput) DOM.searchInput.blur();
          if (DOM.exploreSearchInput) DOM.exploreSearchInput.blur();
          performSearch('');
        }
        closePlaylistPopover();
        closeAuthModal();
        if (state.isQueueOpen) toggleQueueDrawer(false);
      }
    });

    // Queue Drawer Listeners (near Volume)
    if (DOM.queueToggleBtn) {
      DOM.queueToggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleQueueDrawer();
      });
    }
    if (DOM.btnCloseQueue) {
      DOM.btnCloseQueue.addEventListener('click', () => toggleQueueDrawer(false));
    }
    if (DOM.btnClearManualQueue) {
      DOM.btnClearManualQueue.addEventListener('click', () => {
        state.manualQueue = [];
        renderQueueDrawer();
        showToast('MANUAL QUEUE CLEARED');
      });
    }

    // Popover "+ ADD TO QUEUE" option from ":" menu
    if (DOM.popoverAddQueueBtn) {
      DOM.popoverAddQueueBtn.addEventListener('click', () => {
        if (!state.targetPopoverTrack) return;
        addTrackToQueue(state.targetPopoverTrack);
        closePlaylistPopover();
      });
    }

    // User Account Login / Register / Logout Listeners
    updateHeaderAuthUI();

    if (DOM.btnOpenAuth) {
      DOM.btnOpenAuth.addEventListener('click', () => openAuthModal('login'));
    }
    if (DOM.btnCloseAuth) {
      DOM.btnCloseAuth.addEventListener('click', closeAuthModal);
    }
    if (DOM.authModalOverlay) {
      DOM.authModalOverlay.addEventListener('click', (e) => {
        if (e.target === DOM.authModalOverlay) closeAuthModal();
      });
    }
    if (DOM.tabAuthLogin) {
      DOM.tabAuthLogin.addEventListener('click', () => switchAuthTab('login'));
    }
    if (DOM.tabAuthRegister) {
      DOM.tabAuthRegister.addEventListener('click', () => switchAuthTab('register'));
    }

    if (DOM.formAuthLogin) {
      DOM.formAuthLogin.addEventListener('submit', async (e) => {
        e.preventDefault();
        showAuthAlert('');
        const identity = DOM.loginIdentity ? DOM.loginIdentity.value.trim() : '';
        const password = DOM.loginPassword ? DOM.loginPassword.value : '';
        try {
          const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identity, password })
          });
          const data = await res.json();

          if (data.status === 'ok' && data.user) {
            state.currentUser = data.user;
            try { localStorage.setItem('apollo_local_session', JSON.stringify(data.user)); } catch (err) {}
            state.activePlaylist = null;
            updateHeaderAuthUI();
            if (DOM.loginPassword) DOM.loginPassword.value = '';
            closeAuthModal();
            await loadPlaylists();
            if (state.currentView === 'library') openLibraryHome();
            showToast(`WELCOME, ${data.user.username.toUpperCase()} · ACCOUNT ACTIVE`);
            return;
          }

          // Browser localStorage fallback when running on Vercel without Cloud MySQL
          if (data.status === 'fallback') {
            let localUsers = [];
            try {
              localUsers = JSON.parse(localStorage.getItem('apollo_local_users') || '[]') || [];
            } catch (err) {}
            const found = localUsers.find(u =>
              (String(u.username || '').toLowerCase() === identity.toLowerCase() ||
               String(u.email || '').toLowerCase() === identity.toLowerCase()) &&
              u.password === password
            );
            if (!found) {
              showAuthAlert('Username/email atau password salah (atau belum Register di perangkat ini).');
              return;
            }
            const sessionUser = { id: found.id, username: found.username, email: found.email };
            state.currentUser = sessionUser;
            try { localStorage.setItem('apollo_local_session', JSON.stringify(sessionUser)); } catch (err) {}
            state.activePlaylist = null;
            updateHeaderAuthUI();
            if (DOM.loginPassword) DOM.loginPassword.value = '';
            closeAuthModal();
            await loadPlaylists();
            if (state.currentView === 'library') openLibraryHome();
            showToast(`WELCOME, ${sessionUser.username.toUpperCase()} · ACCOUNT ACTIVE`);
            return;
          }

          showAuthAlert(data.message || 'Login failed.');
        } catch (err) {
          showAuthAlert('Network error during login.');
        }
      });
    }

    if (DOM.formAuthRegister) {
      DOM.formAuthRegister.addEventListener('submit', async (e) => {
        e.preventDefault();
        showAuthAlert('');
        const username = DOM.regUsername ? DOM.regUsername.value.trim() : '';
        const email = DOM.regEmail ? DOM.regEmail.value.trim() : '';
        const password = DOM.regPassword ? DOM.regPassword.value : '';
        try {
          const res = await fetch('api/auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password })
          });
          const data = await res.json();

          if (data.status === 'ok' && data.user) {
            state.currentUser = data.user;
            try { localStorage.setItem('apollo_local_session', JSON.stringify(data.user)); } catch (err) {}
            state.activePlaylist = null;
            updateHeaderAuthUI();
            if (DOM.regUsername) DOM.regUsername.value = '';
            if (DOM.regEmail) DOM.regEmail.value = '';
            if (DOM.regPassword) DOM.regPassword.value = '';
            closeAuthModal();
            await loadPlaylists();
            if (state.currentView === 'library') openLibraryHome();
            showToast(`ACCOUNT CREATED · WELCOME ${data.user.username.toUpperCase()}`);
            return;
          }

          // Browser localStorage fallback when running on Vercel without Cloud MySQL
          if (data.status === 'fallback') {
            if (username.length < 3) {
              showAuthAlert('Username minimal 3 karakter.');
              return;
            }
            if (password.length < 4) {
              showAuthAlert('Password minimal 4 karakter.');
              return;
            }
            let localUsers = [];
            try {
              localUsers = JSON.parse(localStorage.getItem('apollo_local_users') || '[]') || [];
            } catch (err) {}
            const exists = localUsers.some(u =>
              String(u.username || '').toLowerCase() === username.toLowerCase() ||
              String(u.email || '').toLowerCase() === email.toLowerCase()
            );
            if (exists) {
              showAuthAlert('Username atau email sudah terdaftar di browser ini, silakan Login.');
              return;
            }
            const newUserRecord = { id: Date.now(), username, email: email.toLowerCase(), password };
            localUsers.push(newUserRecord);
            const sessionUser = { id: newUserRecord.id, username, email: email.toLowerCase() };
            try {
              localStorage.setItem('apollo_local_users', JSON.stringify(localUsers));
              localStorage.setItem('apollo_local_session', JSON.stringify(sessionUser));
            } catch (err) {}

            state.currentUser = sessionUser;
            state.activePlaylist = null;
            updateHeaderAuthUI();
            if (DOM.regUsername) DOM.regUsername.value = '';
            if (DOM.regEmail) DOM.regEmail.value = '';
            if (DOM.regPassword) DOM.regPassword.value = '';
            closeAuthModal();
            await loadPlaylists();
            if (state.currentView === 'library') openLibraryHome();
            showToast(`ACCOUNT CREATED · WELCOME ${sessionUser.username.toUpperCase()}`);
            return;
          }

          showAuthAlert(data.message || 'Registration failed.');
        } catch (err) {
          showAuthAlert('Network error during registration.');
        }
      });
    }

    if (DOM.btnLogout) {
      DOM.btnLogout.addEventListener('click', async () => {
        try {
          await fetch('api/auth.php?action=logout', { method: 'POST' });
        } catch (err) {}
        try {
          localStorage.removeItem('apollo_local_session');
        } catch (err) {}
        state.currentUser = null;
        state.activePlaylist = null;
        updateHeaderAuthUI();
        await loadPlaylists();
        if (state.currentView === 'library') openLibraryHome();
        showToast('SIGNED OUT · SWITCHED TO GUEST SESSION');
      });
    }

    // Popover Close
    if (DOM.popoverCloseBtn) {
      DOM.popoverCloseBtn.addEventListener('click', closePlaylistPopover);
    }

    // Popover Option 1: Like / Unlike Song -> "Liked Songs" playlist
    if (DOM.popoverLikeBtn) {
      DOM.popoverLikeBtn.addEventListener('click', async () => {
        if (!state.targetPopoverTrack) return;
        await toggleLikeTrack(state.targetPopoverTrack);
        closePlaylistPopover();
      });
    }

    // Popover Option 2: Create New User Playlist & Save Track
    const submitNewPlaylistFromPopover = async () => {
      const name = DOM.popoverInput ? DOM.popoverInput.value.trim() : '';
      if (!name) {
        if (DOM.popoverInput) DOM.popoverInput.focus();
        return;
      }
      if (state.targetPopoverTrack) {
        const newPl = await createPlaylist(name, { silentToast: true });
        if (newPl) {
          await addTrackToPlaylist(newPl.id, state.targetPopoverTrack);
        }
        DOM.popoverInput.value = '';
        closePlaylistPopover();
      }
    };

    if (DOM.popoverCreateBtn) {
      DOM.popoverCreateBtn.addEventListener('click', submitNewPlaylistFromPopover);
    }
    if (DOM.popoverInput) {
      DOM.popoverInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          submitNewPlaylistFromPopover();
        }
      });
    }

    // Contextual remove from active playlist
    if (DOM.popoverRemoveTrackBtn) {
      DOM.popoverRemoveTrackBtn.addEventListener('click', async () => {
        if (state.popoverContextPlaylist && state.targetPopoverTrack) {
          await removeTrackFromPlaylist(state.popoverContextPlaylist.id, state.targetPopoverTrack.video_id);
          closePlaylistPopover();
        }
      });
    }

    // Close popover when clicking outside
    document.addEventListener('click', (e) => {
      if (DOM.playlistPopover && DOM.playlistPopover.style.display === 'block') {
        if (
          !DOM.playlistPopover.contains(e.target) &&
          !e.target.closest('.btn-add-popover') &&
          !e.target.closest('#btn-now-add')
        ) {
          closePlaylistPopover();
        }
      }
    });

    // Nav Links
    document.querySelectorAll('[data-nav]').forEach(el => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        const dest = el.dataset.nav;
        handleNavigation(dest);
      });
    });
  }

  function setActiveNavHighlight(dest) {
    document.querySelectorAll('[data-nav]').forEach(link => {
      link.classList.remove('active');
      if (link.dataset.nav === dest || (dest === 'library' && link.dataset.nav === 'playlists')) {
        link.classList.add('active');
      }
    });
  }

  function handleNavigation(destination) {
    setActiveNavHighlight(destination);
    state.activePlaylist = null;
    renderSidebarPlaylists();

    if (destination === 'home') {
      switchView('overview');
      if (DOM.overviewAlbumsSection) DOM.overviewAlbumsSection.style.display = 'block';
      loadHomeRecommendations();
    } else if (destination === 'explore' || destination === 'search') {
      switchView('explore');
      if (DOM.exploreSearchInput) {
        setTimeout(() => DOM.exploreSearchInput.focus(), 80);
      }
    } else if (destination === 'library' || destination === 'playlists') {
      openLibraryHome();
    }
  }

  /**
   * Search Execution with YouTube Music Official Album Art Prioritized Above Unofficial YouTube
   */
  async function performSearch(query) {
    state.currentSearchQuery = query;

    if (state.currentView !== 'overview') {
      setActiveNavHighlight('home');
      switchView('overview');
    }

    if (!query) {
      if (state.searchAbortController) {
        try { state.searchAbortController.abort(); } catch (e) {}
      }
      if (DOM.overviewAlbumsSection) DOM.overviewAlbumsSection.style.display = 'block';
      loadHomeRecommendations();
      return;
    }

    state.searchSequence = (state.searchSequence || 0) + 1;
    const currentSeq = state.searchSequence;

    if (state.searchAbortController) {
      try { state.searchAbortController.abort(); } catch (e) {}
    }
    state.searchAbortController = new AbortController();

    // Hide Recommended Master Albums while searching so official YT Music search results stand front & center
    if (DOM.overviewAlbumsSection) {
      DOM.overviewAlbumsSection.style.display = 'none';
    }

    DOM.tracksSectionTitle.textContent = `SEARCHING YOUTUBE MUSIC & YOUTUBE: "${query.toUpperCase()}"`;
    DOM.tracksTableBody.innerHTML = `
      <tr>
        <td colspan="5" style="text-align: center; padding: 40px; font-family: var(--font-mono); color: var(--signal-orange); font-size: 12px; letter-spacing: 2px;">
          [ CALIBRATING YOUTUBE MUSIC SPECTRUM FOR "${escapeHtml(query.toUpperCase())}"... ]
        </td>
      </tr>
    `;

    try {
      const res = await fetch(`api/search.php?q=${encodeURIComponent(query)}`, {
        signal: state.searchAbortController.signal
      });
      if (!res.ok) {
        throw new Error(`Search HTTP ${res.status}`);
      }
      const data = await res.json();

      if (currentSeq !== state.searchSequence) {
        return;
      }

      if (data.status === 'ok') {
        if (data.tracks && data.tracks.length > 0) {
          const sortedTracks = [...data.tracks].sort((a, b) => {
            const aOfficial = (a.is_official || a.source === 'youtube_music') ? 0 : 1;
            const bOfficial = (b.is_official || b.source === 'youtube_music') ? 0 : 1;
            return aOfficial - bOfficial;
          });
          renderTracksTable(sortedTracks, `SEARCH RESULTS: "${query.toUpperCase()}"`, { isSearch: true });
        } else {
          DOM.tracksMetaCount.textContent = '0 TRACKS';
          DOM.tracksTableBody.innerHTML = `
            <tr>
              <td colspan="5" style="text-align: center; padding: 35px; font-family: var(--font-mono); color: var(--text-dim); letter-spacing: 1px;">
                NO AUDIO SIGNALS FOUND FOR "${escapeHtml(query)}"
              </td>
            </tr>
          `;
        }
      }
    } catch (e) {
      if (e && e.name === 'AbortError') {
        return;
      }
      console.warn('Search error:', e);
      if (currentSeq === state.searchSequence) {
        DOM.tracksMetaCount.textContent = '0 TRACKS';
        DOM.tracksTableBody.innerHTML = `
          <tr>
            <td colspan="5" style="text-align: center; padding: 35px; font-family: var(--font-mono); color: var(--signal-orange); letter-spacing: 1px;">
              SEARCH CONNECTION TIMEOUT — PRESS ENTER TO RETRY "${escapeHtml(query.toUpperCase())}"
            </td>
          </tr>
        `;
      }
    }
  }

  /**
   * View Switching (In-Place, No Page Reload)
   */
  function switchView(viewName) {
    state.currentView = viewName;
    if (DOM.viewOverview) DOM.viewOverview.style.display = (viewName === 'overview') ? 'block' : 'none';
    if (DOM.viewExplore) DOM.viewExplore.style.display = (viewName === 'explore') ? 'flex' : 'none';
    if (DOM.viewLibrary) DOM.viewLibrary.style.display = (viewName === 'library') ? 'flex' : 'none';
    if (DOM.viewArtist) DOM.viewArtist.style.display = (viewName === 'artist') ? 'flex' : 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /**
   * Filter / Search by Genre Tile
   */
  async function filterGenre(genreQuery) {
    showToast(`TUNING FREQUENCY: [${genreQuery.toUpperCase()}]`);
    if (DOM.searchInput) DOM.searchInput.value = genreQuery;
    if (DOM.exploreSearchInput) DOM.exploreSearchInput.value = genreQuery;

    switchView('overview');
    await performSearch(genreQuery);
  }

  /**
   * Open Artist Dossier & Discography
   */
  async function openArtistView(artistName) {
    if (!artistName) return;
    switchView('artist');

    DOM.artistName.textContent = artistName.toUpperCase();
    DOM.artistLocation.textContent = 'FETCHING SIGNAL ARCHIVE...';
    DOM.artistBio.textContent = 'Compiling acoustic dossier and studio discography records...';
    DOM.artistTracksBody.innerHTML = `
      <tr>
        <td colspan="5" style="text-align: center; padding: 25px; font-family: var(--font-mono); color: var(--text-dim);">
          CALIBRATING DISCOGRAPHY RECORDS...
        </td>
      </tr>
    `;

    try {
      const res = await fetch(`api/artist.php?name=${encodeURIComponent(artistName)}`);
      const data = await res.json();

      if (data.status === 'ok' && data.artist) {
        const art = data.artist;
        DOM.artistName.textContent = art.name;
        DOM.artistLocation.textContent = art.location || 'ARCHIVED ARTIST';
        DOM.artistBio.textContent = art.desc || 'Audio recording master catalog.';
        DOM.artistPhoto.src = art.photo || DEFAULT_FALLBACK_THUMB;
        DOM.artistCatalogId.textContent = `CATALOG IDENTIFIER: ${art.catalog_id || 'APOLLO-REF-01'}`;

        renderArtistTracksTable(art.tracks || [], art.name);

        DOM.btnPlayAllArtist.onclick = () => {
          if (art.tracks && art.tracks.length > 0) {
            playTrack(art.tracks[0], art.tracks);
          }
        };
      }
    } catch (e) {
      console.warn('Failed to load artist:', e);
    }
  }

  /**
   * Fetch & Render Live YouTube Music Recommended Albums & Tracks on Home
   */
  async function loadHomeRecommendations() {
    if (DOM.albumsGrid && liveRecommendedAlbums.length === 0) {
      DOM.albumsGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 28px; text-align: center; border: 1px solid var(--border-line); background: var(--bg-darker); font-family: var(--font-mono); font-size: 11px; color: var(--signal-orange); letter-spacing: 0.08em;">
          [ LOADING LIVE RECOMMENDATIONS FROM YOUTUBE MUSIC... ]
        </div>
      `;
    }

    try {
      const res = await fetch('api/search.php');
      const data = await res.json();
      if (data.status === 'ok') {
        if (Array.isArray(data.recommended_albums) && data.recommended_albums.length > 0) {
          liveRecommendedAlbums = data.recommended_albums;
        }
        if (Array.isArray(data.tracks) && data.tracks.length > 0) {
          liveRecommendedTracks = data.tracks;
        }
      }
    } catch (e) {
      console.warn('Failed to fetch YouTube Music recommendations:', e);
    }

    renderRecommendedAlbums(liveRecommendedAlbums);

    const homeTracks = state.recentTracks.length > 0 ? state.recentTracks : liveRecommendedTracks;
    const heading = state.recentTracks.length > 0
      ? 'RECENTLY PLAYED · LISTENING HISTORY'
      : 'YOUTUBE MUSIC · RECOMMENDED TRACKS';
    renderTracksTable(homeTracks, heading);

    // Initialize player bar with first YouTube Music track if none active yet
    if (!state.currentTrack && homeTracks.length > 0) {
      state.currentTrack = homeTracks[0];
      setNowPlayingInfo(homeTracks[0]);
      state.queue = [...homeTracks];
      state.queueIndex = 0;
      state.queueSourceName = 'YouTube Music';
    }
    renderQueueDrawer();
  }

  function renderRecommendedAlbums(albums = []) {
    if (!DOM.albumsGrid) return;
    if (DOM.overviewAlbumsTitle) DOM.overviewAlbumsTitle.textContent = 'RECOMMENDED SONGS · YOUTUBE MUSIC';
    if (DOM.overviewAlbumsMeta) DOM.overviewAlbumsMeta.textContent = 'INDIVIDUAL TRACKS · PLAY DIRECTLY';

    DOM.albumsGrid.innerHTML = '';

    if (!albums || albums.length === 0) {
      DOM.albumsGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 24px; text-align: center; border: 1px solid var(--border-line); font-family: var(--font-mono); font-size: 11px; color: var(--text-dim);">
          NO YOUTUBE MUSIC RECOMMENDATIONS AVAILABLE RIGHT NOW
        </div>
      `;
      return;
    }

    albums.slice(0, 4).forEach(album => {
      const card = document.createElement('article');
      card.className = 'album-card recommended-song-card';
      const lead = album.leadTrack || null;
      const fallbackYtThumb = lead && lead.video_id
        ? `https://i.ytimg.com/vi/${encodeURIComponent(lead.video_id)}/hqdefault.jpg`
        : DEFAULT_FALLBACK_THUMB;

      card.innerHTML = `
        <div class="album-thumb-wrap">
          <img class="album-thumb" referrerpolicy="no-referrer" src="${escapeHtml(album.artwork || fallbackYtThumb)}" alt="" loading="lazy">
          <button type="button" class="album-play-btn" title="Play ${escapeHtml(lead ? lead.title : album.title)}">
            <span class="material-symbols-outlined" style="font-size: 22px;">play_arrow</span>
          </button>
          <span class="album-badge">${escapeHtml(album.badge || 'SONG')}</span>
        </div>
        <div class="album-info">
          <h3 class="album-title" title="${escapeHtml(album.title)}">${escapeHtml(album.title)}</h3>
          <p class="album-artist">${escapeHtml(album.artist)}</p>
        </div>
      `;

      const imgEl = card.querySelector('.album-thumb');
      if (imgEl) {
        imgEl.addEventListener('error', () => {
          if (imgEl.src !== fallbackYtThumb && !imgEl.dataset.retriedYt) {
            imgEl.dataset.retriedYt = '1';
            imgEl.src = fallbackYtThumb;
          } else {
            imgEl.src = DEFAULT_FALLBACK_THUMB;
          }
        });
      }

      // ONLY the Play button triggers action; clicking the card/image does nothing
      const playBtn = card.querySelector('.album-play-btn');
      if (playBtn && lead) {
        playBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          playTrack(lead, liveRecommendedTracks.length > 0 ? liveRecommendedTracks : [lead], { sourceName: 'YouTube Music' });
        });
      }

      DOM.albumsGrid.appendChild(card);
    });
  }

  /**
   * Client-side song validator to filter non-song videos
   */
  function isCleanSong(track) {
    if (!track || !track.title) return false;
    const title = track.title.toLowerCase();
    const artist = (track.artist || '').toLowerCase();
    const dur = track.duration || '';
    const combined = `${title} ${artist}`;

    if (dur.includes(':')) {
      const parts = dur.split(':').map(Number);
      if (parts.length === 3) return false;
      if (parts.length === 2 && parts[0] > 11) return false;
      if (parts.length === 2 && parts[0] === 0 && parts[1] < 45) return false;
    }

    const activeQuery = (state.currentSearchQuery || '').toLowerCase();
    const seekingIndian = /\b(hindi|bollywood|punjabi|bhojpuri|tamil|telugu|malayalam|kannada|urdu|indian|arijit|neha kakkar|badshah|diljit)\b/i.test(activeQuery);
    if (!seekingIndian) {
      const indianPattern = /\b(t-series|tseries|zee music|sony music india|tips official|tips music|saregama|yrf|speed records|aditya music|lahari music|wave music|shemaroo|times music|desi music|hindi(\s+old)?\s+songs?|bollywood|bhojpuri|punjabi\s+songs?|telugu\s+songs?|tamil\s+songs?|purane\s+gane|sadabahar|arijit\s+singh|udit\s+narayan|kumar\s+sanu|alka\s+yagnik|neha\s+kakkar|lata\s+mangeshkar|kishore\s+kumar|shashwat\s+sachdev|anuradha\s+paudwal)\b/i;
      if (indianPattern.test(combined)) {
        return false;
      }
    }

    const nonSongRegex = /\b(reaction|reacts|reacting|interview|podcast|tutorial|how to play|documentary|full album|compilation|greatest\s+(?:\w+\s+)?hits|best\s+(?:\w+\s+)?songs|best of\b|music collection|legendary\s+\w+|playlist|kompilasi|kumpulan lagu|1 hour|10 hours|hour loop|unboxing|review|trailer|teaser|gameplay|walkthrough|shorts|vlog|episode)\b/i;
    if (nonSongRegex.test(title) || nonSongRegex.test(String(track.album || '').toLowerCase())) return false;

    return true;
  }

  /**
   * Render Clean Tracks Table (Home Reference Tracks & Prioritized Search Results)
   */
  function renderTracksTable(tracks, sectionTitle = 'RECENTLY PLAYED / REFERENCE TRACKS', opts = {}) {
    if (!DOM.tracksTableBody) return;
    const cleanTracks = (tracks || []).filter(isCleanSong);
    DOM.tracksSectionTitle.textContent = sectionTitle;
    DOM.tracksMetaCount.textContent = `${cleanTracks.length} TRACKS`;
    DOM.tracksTableBody.innerHTML = '';

    let insertedUnofficialDivider = false;
    const hasOfficialInSearch = opts.isSearch && cleanTracks.some(t => t.is_official || t.source === 'youtube_music');

    cleanTracks.forEach((track, index) => {
      const isOfficialYtm = Boolean(track.is_official || track.source === 'youtube_music');

      // Insert clear visual divider before unofficial YouTube videos in search results
      if (opts.isSearch && hasOfficialInSearch && !isOfficialYtm && !insertedUnofficialDivider) {
        insertedUnofficialDivider = true;
        const divTr = document.createElement('tr');
        divTr.className = 'tracks-divider-row';
        divTr.innerHTML = `
          <td colspan="5">YOUTUBE · UNOFFICIAL / COMMUNITY VIDEO UPLOADS</td>
        `;
        DOM.tracksTableBody.appendChild(divTr);
      }

      const tr = document.createElement('tr');
      tr.dataset.videoId = track.video_id;
      if (state.currentTrack && state.currentTrack.video_id === track.video_id) {
        tr.classList.add('playing');
      }

      const numStr = String(index + 1).padStart(2, '0');
      const sourceBadgeHtml = opts.isSearch
        ? (isOfficialYtm
            ? '<span class="track-source-badge ytm">YT MUSIC</span>'
            : '<span class="track-source-badge yt">YOUTUBE</span>')
        : '';
      const subAlbumHtml = (opts.isSearch && isOfficialYtm && track.album)
        ? `<div style="font-family: var(--font-mono); font-size: 10px; color: var(--text-dim); margin-top: 2px;">${escapeHtml(track.album)}</div>`
        : `<div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-dim);" class="sm-artist-mobile">${escapeHtml(track.artist)}</div>`;

      tr.innerHTML = `
        <td class="col-num">
          <span class="row-index">${numStr}</span>
          <span class="material-symbols-outlined row-play-icon">play_arrow</span>
        </td>
        <td class="col-title">
          <div class="track-title-cell">
            <img class="track-inline-thumb" referrerpolicy="no-referrer" src="${escapeHtml(track.thumbnail || DEFAULT_FALLBACK_THUMB)}" alt="">
            <div style="min-width: 0;">
              <div>
                <span class="track-title-text">${escapeHtml(track.title)}</span>
                ${sourceBadgeHtml}
              </div>
              ${subAlbumHtml}
            </div>
          </div>
        </td>
        <td class="col-artist">
          <span class="artist-click-link">${escapeHtml(track.artist)}</span>
        </td>
        <td class="col-dur">${escapeHtml(track.duration || '03:30')}</td>
        <td class="col-action">
          <button type="button" class="btn-add-popover" title="Track options">:</button>
        </td>
      `;

      tr.addEventListener('click', (e) => {
        if (e.target.closest('.btn-add-popover') || e.target.closest('.artist-click-link')) return;
        const srcLabel = opts.isSearch && state.currentSearchQuery
          ? state.currentSearchQuery
          : 'YouTube Music';
        playTrack(track, cleanTracks, { sourceName: srcLabel });
      });

      const artLink = tr.querySelector('.artist-click-link');
      if (artLink) {
        artLink.addEventListener('click', (e) => {
          e.stopPropagation();
          openArtistView(track.artist);
        });
      }

      const optBtn = tr.querySelector('.btn-add-popover');
      if (optBtn) {
        optBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          openPlaylistPopover(e, track, { mode: 'track_menu' });
        });
      }

      DOM.tracksTableBody.appendChild(tr);
    });
  }

  /**
   * Render Artist Discography Table
   */
  function renderArtistTracksTable(tracks, artistName) {
    if (!DOM.artistTracksBody) return;
    const cleanTracks = (tracks || []).filter(isCleanSong);
    DOM.artistTracksBody.innerHTML = '';

    cleanTracks.forEach((track, index) => {
      const tr = document.createElement('tr');
      tr.dataset.videoId = track.video_id;
      if (state.currentTrack && state.currentTrack.video_id === track.video_id) {
        tr.classList.add('playing');
      }

      const numStr = String(index + 1).padStart(2, '0');
      track.artist = track.artist || artistName;

      tr.innerHTML = `
        <td class="col-num">
          <span class="row-index">${numStr}</span>
          <span class="material-symbols-outlined row-play-icon">play_arrow</span>
        </td>
        <td class="col-title">
          <span class="track-title-text">${escapeHtml(track.title)}</span>
        </td>
        <td class="col-artist">${escapeHtml(track.album || 'Studio Master Archive')}</td>
        <td class="col-dur">${escapeHtml(track.duration || '03:40')}</td>
        <td class="col-action">
          <button type="button" class="btn-add-popover" title="Track options">:</button>
        </td>
      `;

      tr.addEventListener('click', (e) => {
        if (e.target.closest('.btn-add-popover')) return;
        playTrack(track, cleanTracks, { sourceName: artistName || 'Artist' });
      });

      const optBtn = tr.querySelector('.btn-add-popover');
      if (optBtn) {
        optBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          openPlaylistPopover(e, track, { mode: 'track_menu' });
        });
      }

      DOM.artistTracksBody.appendChild(tr);
    });
  }

  /**
   * Floating Popover Modal for ":" (Like + Save to User Playlist) & "+" on Bottom Music Player
   */
  function openPlaylistPopover(event, track, opts = {}) {
    if (!DOM.playlistPopover || !track) return;

    const triggerEl = event ? (event.currentTarget || event.target) : null;
    if (
      DOM.playlistPopover.style.display === 'block' &&
      triggerEl &&
      state.popoverTriggerEl === triggerEl &&
      state.targetPopoverTrack &&
      state.targetPopoverTrack.video_id === track.video_id
    ) {
      closePlaylistPopover();
      return;
    }

    state.targetPopoverTrack = track;
    state.popoverTriggerEl = triggerEl;
    state.popoverContextPlaylist = opts.contextPlaylist || null;

    if (DOM.popoverHeaderLabel) {
      DOM.popoverHeaderLabel.textContent = (opts.mode === 'player_add')
        ? 'ADD TO PLAYLIST'
        : 'TRACK OPTIONS [:]';
    }
    if (DOM.popoverQueueSection) {
      DOM.popoverQueueSection.style.display = (opts.mode === 'player_add') ? 'none' : 'block';
    }
    if (DOM.popoverTargetTrack) {
      DOM.popoverTargetTrack.textContent = `${track.title} — ${track.artist || ''}`;
    }

    // 1. Update Option 1: Like / Liked Songs state
    const isLiked = state.likedTrackIds.has(track.video_id);
    if (DOM.popoverLikeBtn) {
      DOM.popoverLikeBtn.classList.toggle('liked', isLiked);
    }
    if (DOM.popoverLikeText) {
      DOM.popoverLikeText.textContent = isLiked
        ? `LIKED · IN ${LIKED_PLAYLIST_NAME.toUpperCase()}`
        : `LIKE · SAVE TO ${LIKED_PLAYLIST_NAME.toUpperCase()}`;
    }

    // 2. Populate Option 2: User-Created Playlists (excluding Liked Songs since Option 1 handles it)
    DOM.popoverList.innerHTML = '';
    const userPlaylists = state.playlists.filter(pl => !isLikedPlaylist(pl));

    if (userPlaylists.length === 0) {
      const hint = document.createElement('div');
      hint.className = 'popover-empty-hint';
      hint.textContent = 'No user playlists yet — create one below:';
      DOM.popoverList.appendChild(hint);
    } else {
      userPlaylists.forEach(pl => {
        const item = document.createElement('div');
        item.className = 'popover-playlist-item';
        item.innerHTML = `
          <span class="popover-playlist-name">${escapeHtml(pl.name)}</span>
          <button type="button" class="btn-popover-commit">+ SAVE</button>
        `;
        item.addEventListener('click', async () => {
          await addTrackToPlaylist(pl.id, state.targetPopoverTrack);
          closePlaylistPopover();
        });
        DOM.popoverList.appendChild(item);
      });
    }

    // Contextual remove button when viewing inside a playlist in Library
    if (DOM.popoverRemoveTrackBtn) {
      if (state.popoverContextPlaylist) {
        DOM.popoverRemoveTrackBtn.style.display = 'inline-flex';
        const ctxName = isLikedPlaylist(state.popoverContextPlaylist)
          ? LIKED_PLAYLIST_NAME
          : state.popoverContextPlaylist.name;
        DOM.popoverRemoveTrackBtn.textContent = `✕ REMOVE FROM "${ctxName.toUpperCase()}"`;
      } else {
        DOM.popoverRemoveTrackBtn.style.display = 'none';
      }
    }

    // Display & Smart Viewport Positioning
    const pop = DOM.playlistPopover;
    pop.style.display = 'block';

    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const popWidth = pop.offsetWidth || 280;
    const popHeight = pop.offsetHeight || 300;
    const playerBarHeight = 84;

    if (triggerEl && triggerEl.getBoundingClientRect) {
      const rect = triggerEl.getBoundingClientRect();

      if (opts.mode === 'player_add' || triggerEl === DOM.nowAddBtn) {
        // Anchor cleanly right ABOVE the bottom music player "+" button
        const left = Math.max(12, Math.min(rect.left, vw - popWidth - 12));
        pop.style.left = `${left}px`;
        pop.style.right = 'auto';
        pop.style.bottom = `${vh - rect.top + 10}px`;
        pop.style.top = 'auto';
      } else {
        // Anchor near ":" button without clipping off bottom player bar or top edge
        pop.style.bottom = 'auto';
        const left = Math.max(12, Math.min(rect.right - popWidth, vw - popWidth - 12));
        pop.style.left = `${left}px`;
        pop.style.right = 'auto';

        let top = rect.bottom + 6;
        if (top + popHeight > vh - playerBarHeight) {
          top = Math.max(12, rect.top - popHeight - 6);
        }
        pop.style.top = `${top}px`;
      }
    } else {
      pop.style.bottom = 'auto';
      pop.style.top = '100px';
      pop.style.right = '28px';
      pop.style.left = 'auto';
    }
  }

  function closePlaylistPopover() {
    if (DOM.playlistPopover) {
      DOM.playlistPopover.style.display = 'none';
    }
    state.popoverTriggerEl = null;
    state.popoverContextPlaylist = null;
  }

  /**
   * Industrial Toast Notification
   */
  let toastTimer = null;
  function showToast(msg) {
    DOM.toastText.textContent = msg;
    DOM.systemToast.style.display = 'flex';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      DOM.systemToast.style.display = 'none';
    }, 3200);
  }

  /**
   * MediaSession API
   */
  function setupMediaSession() {
    if ('mediaSession' in navigator) {
      navigator.mediaSession.setActionHandler('play', () => togglePlayPause());
      navigator.mediaSession.setActionHandler('pause', () => togglePlayPause());
      navigator.mediaSession.setActionHandler('previoustrack', () => playPreviousTrack());
      navigator.mediaSession.setActionHandler('nexttrack', () => playNextTrack());
      navigator.mediaSession.setActionHandler('seekto', (details) => {
        if (details.seekTime === undefined) return;
        if (state.playbackBackend === 'yt_bridge' && state.ytPlayer && state.ytApiReady) {
          state.ytPlayer.seekTo(details.seekTime, true);
        } else if (state.audio.duration) {
          state.audio.currentTime = details.seekTime;
        }
      });
    }
  }

  function updateMediaSessionPlaybackState() {
    if ('mediaSession' in navigator && state.currentTrack) {
      navigator.mediaSession.playbackState = state.isPlaying ? 'playing' : 'paused';
      navigator.mediaSession.metadata = new MediaMetadata({
        title: state.currentTrack.title,
        artist: state.currentTrack.artist,
        album: state.currentTrack.album || 'Apollo Minimalist Audio',
        artwork: [
          { src: state.currentTrack.thumbnail || DEFAULT_FALLBACK_THUMB, sizes: '512x512', type: 'image/jpeg' }
        ]
      });
    }
  }

  /**
   * Utilities
   */
  function formatTime(seconds) {
    const s = Math.floor(seconds || 0);
    const m = Math.floor(s / 60);
    const rem = s % 60;
    return `${String(m).padStart(2, '0')}:${String(rem).padStart(2, '0')}`;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Global window helpers for inline triggers
  window.switchView = switchView;
  window.openArtistView = openArtistView;
  window.togglePlaylistPopover = openPlaylistPopover;
  window.filterGenre = filterGenre;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
