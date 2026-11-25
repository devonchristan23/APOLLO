<?php
/**
 * APOLLO — Halaman Utama Aplikasi (100% Full PHP Native Modular)
 * Merakit seluruh komponen tampilan dari folder views/partials/ dan views/pages/.
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=utf-8');

// Cache-buster otomatis untuk CSS & JS (mengikuti commit Vercel atau waktu edit file lokal)
$assetVer = substr((string)(getenv('VERCEL_GIT_COMMIT_SHA') ?: ''), 0, 8);
if ($assetVer === '') {
    $assetVer = (string)((@filemtime(__DIR__ . '/../assets/player.js') ?: 0) + (@filemtime(__DIR__ . '/../assets/style.css') ?: 0) ?: '20261125v5');
}

// Catat kunjungan ke visitor_logs & baca status login user saat ini
apolloTrackVisitor($_SERVER['REQUEST_URI'] ?? '/');
$initialUser = apolloGetCurrentUser();

require __DIR__ . '/../views/partials/head.php';
?>
<body>

<div id="app-container">
  <?php require __DIR__ . '/../views/partials/sidebar.php'; ?>

  <main id="main-view">
    <?php
      require __DIR__ . '/../views/partials/top_header.php';
      require __DIR__ . '/../views/pages/home_view.php';
      require __DIR__ . '/../views/pages/explore_view.php';
      require __DIR__ . '/../views/pages/library_view.php';
      require __DIR__ . '/../views/pages/artist_view.php';
    ?>
  </main>

  <?php
    require __DIR__ . '/../views/partials/player_bar.php';
    require __DIR__ . '/../views/partials/queue_drawer.php';
    require __DIR__ . '/../views/partials/mobile_nav.php';
    require __DIR__ . '/../views/partials/modals.php';
  ?>
</div>

<script>
  window.APOLLO_INITIAL_USER = <?= json_encode($initialUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/player.js?v=<?= htmlspecialchars($assetVer) ?>"></script>
</body>
</html>
