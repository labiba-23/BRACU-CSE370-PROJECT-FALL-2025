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

/* -------------------------------------------------
   1. Ensure VISITOR exists
------------------------------------------------- */
$stmt = $pdo->prepare("SELECT ID FROM visitor WHERE ID = ? LIMIT 1");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    $pdo->prepare(
        "INSERT INTO visitor (ID, Reward_points, Privacy) VALUES (?, 0, 0)"
    )->execute([$userId]);
}

/* -------------------------------------------------
   2. Ensure SUBSCRIPTION exists
------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT Wishlist FROM subscription WHERE Visitor_ID = ? LIMIT 1"
);
$stmt->execute([$userId]);
$row = $stmt->fetch();

if (!$row) {
    $sId = random_int(100000, 999999);
    $pdo->prepare(
        "INSERT INTO subscription (S_ID, Subtitles, Wishlist, Visitor_ID)
         VALUES (?, '', '', ?)"
    )->execute([$sId, $userId]);
    $wishlistStr = "";
} else {
    $wishlistStr = (string)($row["Wishlist"] ?? "");
}

/* -------------------------------------------------
   3. Convert wishlist string → array
------------------------------------------------- */
$watchlist = [];
if (trim($wishlistStr) !== "") {
    $watchlist = array_values(
        array_filter(array_map("trim", explode(",", $wishlistStr)))
    );
}

/* -------------------------------------------------
   4. REMOVE from watchlist
------------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove"])) {
    $remove = trim((string)$_POST["remove"]);

    $watchlist = array_values(
        array_filter($watchlist, fn($m) => $m !== $remove)
    );

    $newWishlist = implode(", ", $watchlist);
    $stmt = $pdo->prepare(
        "UPDATE subscription SET Wishlist = ? WHERE Visitor_ID = ?"
    );
    $stmt->execute([$newWishlist, $userId]);

    $message = "Removed from watchlist.";
    $isOk = true;
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
          <div class="watch-card">
            <div class="watch-poster">
              <?= h(mb_strtoupper(mb_substr($title, 0, 1))) ?>
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
