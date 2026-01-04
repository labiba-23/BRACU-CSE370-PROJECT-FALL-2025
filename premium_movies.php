<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/premium_helper.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
if (!isPremium($pdo, $userId)) {
    header("Location: buy_premium_page.php");
    exit;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

$stmt = $pdo->query("
    SELECT premium_movie_id, title, description, youtube_id, youtube_url, poster, is_active, created_at
    FROM premium_movies
    WHERE is_active = 1
    ORDER BY created_at DESC
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Premium Movies - TheatreFlix</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    
    .premium-tag{
      position:absolute;
      top:10px;
      left:10px;
      padding:4px 10px;
      border-radius:999px;
      font-size:11px;
      font-weight:900;
      background: rgba(255,111,183,0.18);
      border: 1px solid rgba(255,111,183,0.35);
      color:#fff;
      backdrop-filter: blur(6px);
    }
    .pm-card { position: relative; }
    .pm-btnrow { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
  </style>
</head>

<body class="app-body">
  <div class="app-page">

    <div class="app-topbar">
      <div class="app-brand">THEATRE<span>FLIX</span></div>
      <div class="app-nav">

        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>

    <div class="app-card">
      <div class="app-title pink">Premium Movies</div>
      <div class="app-sub">Exclusive content for Premium members</div>

      <?php if (!$movies): ?>
        <div class="notice err">No premium movies available yet.</div>
        <div class="row">
          <a class="btn btn-ghost" href="premium_upload.php">Add Premium Movie</a>
          <a class="btn btn-ghost" href="premium_portal.php">Back</a>
        </div>
      <?php else: ?>

        <div class="watchlist-grid">
          <?php foreach ($movies as $m): ?>
            <?php
              $poster = trim((string)($m["poster"] ?? ""));
              if ($poster === "") $poster = "images/posters/placeholder.jpg";
            ?>
            <div class="watch-card pm-card">
              <div class="premium-tag">PREMIUM</div>

              <div class="watch-poster">
                <a href="premium_watch.php?id=<?= (int)$m["premium_movie_id"] ?>">
                  <img src="<?= h($poster) ?>" alt="<?= h((string)$m["title"]) ?>">
                </a>
              </div>

              <div class="watch-info">
                <div style="font-weight:900; font-size:18px; margin-bottom:6px;">
                  <?= h((string)$m["title"]) ?>
                </div>
                <div class="app-sub" style="margin-bottom:10px;">
                  <?= h((string)$m["description"]) ?>
                </div>

                <div class="pm-btnrow">
                  <a class="btn" href="premium_watch.php?id=<?= (int)$m["premium_movie_id"] ?>">▶ Watch</a>
                  <a class="btn btn-ghost" target="_blank" href="<?= h((string)$m["youtube_url"]) ?>">YouTube</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="row" style="margin-top:16px;">
          <a class="btn btn-ghost" href="premium_upload.php">Add Premium Movie</a>
          <a class="btn btn-ghost" href="premium_portal.php">Back to Portal</a>
        </div>

      <?php endif; ?>
    </div>

  </div>
</body>
</html>

