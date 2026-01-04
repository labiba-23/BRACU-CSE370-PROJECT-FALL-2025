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

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: premium_movies.php");
    exit;
}

$stmt = $pdo->prepare("
  SELECT premium_movie_id, title, description, youtube_id, youtube_url, poster
  FROM premium_movies
  WHERE premium_movie_id = ? AND is_active = 1
  LIMIT 1
");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("Premium movie not found or inactive.");
}

$ytId = trim((string)$m["youtube_id"]);
if ($ytId === "") {
    
    $url = (string)$m["youtube_url"];
    if (preg_match('~v=([a-zA-Z0-9_-]{6,})~', $url, $mm)) $ytId = $mm[1];
    elseif (preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~', $url, $mm)) $ytId = $mm[1];
}

if ($ytId === "") {
    die("YouTube ID missing for this movie.");
}

$embed = "https://www.youtube.com/embed/" . rawurlencode($ytId) . "?rel=0";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= h((string)$m["title"]) ?> - Premium</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .video-wrap{
      width:100%;
      aspect-ratio: 16 / 9;
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.10);
      background: rgba(0,0,0,0.35);
    }
    .video-wrap iframe{
      width:100%;
      height:100%;
      border:0;
      display:block;
    }
  </style>
</head>

<body class="app-body">
  <div class="app-page">

    <div class="app-topbar">
      <div class="app-brand">THEATRE<span>FLIX</span></div>
      <div class="app-nav">
        <a href="premium_movies.php">Premium Movies</a>
        <a href="premium_portal.php">Premium Portal</a>
        <a href="dashboard.php">Dashboard</a>
      </div>
    </div>

    <div class="app-card">
      <div class="app-title pink"><?= h((string)$m["title"]) ?></div>
      <div class="app-sub"><?= h((string)$m["description"]) ?></div>

      <div class="video-wrap">
        <iframe
          src="<?= h($embed) ?>"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowfullscreen>
        </iframe>
      </div>

      <div class="row" style="margin-top:16px;">
        <a class="btn btn-ghost" href="premium_movies.php">Back to Premium Movies</a>
        <a class="btn btn-ghost" target="_blank" href="<?= h((string)$m["youtube_url"]) ?>">Open on YouTube</a>
      </div>
    </div>

  </div>
</body>
</html>

