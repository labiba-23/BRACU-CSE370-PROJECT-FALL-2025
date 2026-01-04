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

function extractYoutubeId(string $url): string {
    $url = trim($url);
    if ($url === "") return "";

    
    if (preg_match('~^[a-zA-Z0-9_-]{6,}$~', $url)) return $url;

    if (preg_match('~v=([a-zA-Z0-9_-]{6,})~', $url, $m)) return $m[1];
    if (preg_match('~youtu\.be/([a-zA-Z0-9_-]{6,})~', $url, $m)) return $m[1];
    if (preg_match('~youtube\.com/embed/([a-zA-Z0-9_-]{6,})~', $url, $m)) return $m[1];

    return "";
}

$msg = "";
$isOk = true;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim((string)($_POST["title"] ?? ""));
    $desc  = trim((string)($_POST["description"] ?? ""));
    $ytUrl = trim((string)($_POST["youtube_url"] ?? ""));
    $poster = trim((string)($_POST["poster"] ?? "images/posters/placeholder.jpg"));

    $ytId = extractYoutubeId($ytUrl);

    if ($title === "" || $ytUrl === "" || $ytId === "") {
        $msg = "Please provide Title + valid YouTube link (or ID).";
        $isOk = false;
    } else {
        try {
            $stmt = $pdo->prepare("
              INSERT INTO premium_movies (title, description, youtube_id, youtube_url, poster, is_active, created_at)
              VALUES (?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$title, $desc, $ytId, $ytUrl, $poster]);

            $msg = "Premium movie added successfully ✅";
            $isOk = true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $isOk = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Premium Movie - TheatreFlix</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="style.css?v=999">
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
      <div class="app-title pink">Add Premium Movie</div>
      <div class="app-sub">Paste YouTube link + choose poster path</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <form method="POST">
        <label class="app-label">Title</label>
        <input class="app-input" name="title" required>

        <label class="app-label">Description</label>
        <textarea class="app-textarea" name="description"></textarea>

        <label class="app-label">YouTube Link (or just YouTube ID)</label>
        <input class="app-input" name="youtube_url" placeholder="https://www.youtube.com/watch?v=xxxx" required>

        <label class="app-label">Poster Path (optional)</label>
        <input class="app-input" name="poster" value="images/posters/placeholder.jpg">

        <div class="row">
          <button class="btn" type="submit">Save</button>
          <a class="btn btn-ghost" href="premium_movies.php">Back</a>
        </div>
      </form>
    </div>

  </div>
</body>
</html>
