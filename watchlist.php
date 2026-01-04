<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$message = "";
$isOk = true;

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

// Ensure visitor row exists
$stmt = $pdo->prepare("SELECT ID FROM visitor WHERE ID = ? LIMIT 1");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO visitor (ID, Reward_points, Privacy) VALUES (?, 0, 0)")
        ->execute([$userId]);
}


$stmt = $pdo->prepare("SELECT Wishlist FROM subscription WHERE Visitor_ID = ? LIMIT 1");
$stmt->execute([$userId]);
$row = $stmt->fetch();

if (!$row) {
    $sId = random_int(100000, 999999);
    $pdo->prepare("INSERT INTO subscription (S_ID, Subtitles, Wishlist, Visitor_ID) VALUES (?, '', '', ?)")
        ->execute([$sId, $userId]);
    $wishlistStr = "";
} else {
    $wishlistStr = (string)($row["Wishlist"] ?? "");
}

$watchlist = [];
if (trim($wishlistStr) !== "") {
    $watchlist = array_values(array_filter(array_map("trim", explode(",", $wishlistStr))));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove"])) {
    $remove = trim((string)$_POST["remove"]);

    $watchlist = array_values(array_filter($watchlist, fn($m) => $m !== $remove));

    $newWishlist = implode(", ", $watchlist);
    $stmt = $pdo->prepare("UPDATE subscription SET Wishlist = ? WHERE Visitor_ID = ?");
    $stmt->execute([$newWishlist, $userId]);

    $message = "Removed from watchlist.";
    $isOk = true;
}


$postersByTitle = [];
$trailersByTitle = [];

if ($watchlist) {
    $placeholders = implode(",", array_fill(0, count($watchlist), "?"));

    $sql = "
      SELECT
        COALESCE(NULLIF(TRIM(on_movie),''), NULLIF(TRIM(up_movie),'')) AS title,
        poster,
        trailer_url
      FROM movie_catalogue
      WHERE COALESCE(NULLIF(TRIM(on_movie),''), NULLIF(TRIM(up_movie),'')) IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($watchlist);

    foreach ($stmt->fetchAll() as $r) {
        $t = (string)$r["title"];
        $postersByTitle[$t] = trim((string)($r["poster"] ?? ""));
        $trailersByTitle[$t] = trim((string)($r["trailer_url"] ?? ""));
    }
}

function safePoster(string $poster): string {
    $poster = trim($poster);
    return $poster !== "" ? $poster : "assets/placeholder.jpg";
}

function safeTrailer(string $url): string {
    $url = trim($url);
    if ($url === "") return "";
    if (!preg_match('#^https?://#i', $url)) return "";
    return $url;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Watchlist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
  <div class="card" style="max-width:900px;">
    <h1>🎬 My Watchlist</h1>
    <p class="sub">Movies & series saved from the catalogue</p>

    <?php if ($message): ?>
      <div class="msg <?= $isOk ? "ok" : "error" ?>">
        <?= h($message) ?>
      </div>
    <?php endif; ?>

    <?php if (!$watchlist): ?>
      <p class="sub">
        Your watchlist is empty.<br>
        Browse the catalogue and add movies you want to watch.
      </p>
    <?php else: ?>
      <div class="watchlist-grid">
        <?php foreach ($watchlist as $title): ?>
          <?php
            $poster = safePoster($postersByTitle[$title] ?? "");
            $ytLink = safeTrailer($trailersByTitle[$title] ?? "");
          ?>
          <div class="watch-card">
            <div class="watch-poster" style="padding:0; overflow:hidden;">
              <?php if ($ytLink !== ""): ?>
                <a href="<?= h($ytLink) ?>" target="_blank" rel="noopener" style="display:block; width:100%; height:100%;">
              <?php endif; ?>

              <img
                src="<?= h($poster) ?>"
                alt="<?= h($title) ?>"
                style="width:100%; height:100%; object-fit:cover; display:block;"
                onerror="this.onerror=null;this.src='assets/placeholder.jpg';"
              >

              <?php if ($ytLink !== ""): ?>
                </a>
              <?php endif; ?>
            </div>

            <div class="watch-info">
              <strong><?= h($title) ?></strong>
              <small>Movie / Series</small>

              <form method="POST" style="margin-top:8px;">
                <input type="hidden" name="remove" value="<?= h($title) ?>">
                <button type="submit" class="btn small danger">
                  Remove
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="link" style="margin-top:20px;">
      <a href="movie_catalogue.php">Browse Catalogue</a> •
      <a href="dashboard.php">Dashboard</a>
    </div>
  </div>
</div>

</body>
</html>

