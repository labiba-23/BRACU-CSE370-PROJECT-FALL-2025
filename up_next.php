<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$msg = "";
$isOk = true;

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("SELECT ID FROM visitor WHERE ID = ? LIMIT 1");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO visitor (ID, Reward_points, Privacy) VALUES (?, 0, 0)");
    $stmt->execute([$userId]);
}


$stmt = $pdo->prepare("SELECT Wishlist FROM subscription WHERE Visitor_ID = ? LIMIT 1");
$stmt->execute([$userId]);
$sub = $stmt->fetch();

if (!$sub) {
    $sId = random_int(100000, 999999);
    $stmt = $pdo->prepare("INSERT INTO subscription (S_ID, Subtitles, Wishlist, Visitor_ID) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sId, "", "", $userId]);
    $wishlistStr = "";
} else {
    $wishlistStr = (string)($sub["Wishlist"] ?? "");
}


$stmt = $pdo->query("
    SELECT DISTINCT up_movie
    FROM movie_catalogue
    WHERE up_movie IS NOT NULL AND TRIM(up_movie) <> ''
    ORDER BY up_movie ASC
");
$upcoming = $stmt->fetchAll(PDO::FETCH_COLUMN);


$watchlist = [];
if (trim($wishlistStr) !== "") {
    $watchlist = array_values(array_filter(array_map("trim", explode(",", $wishlistStr))));
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected = trim($_POST["up_movie"] ?? "");

    if ($selected === "") {
        $msg = "Please select a movie.";
        $isOk = false;
    } elseif (!in_array($selected, $upcoming, true)) {
        $msg = "Invalid selection.";
        $isOk = false;
    } else {
       
        if (!in_array($selected, $watchlist, true)) {
            $watchlist[] = $selected;
        }

        $newWishlist = implode(", ", $watchlist);
        $stmt = $pdo->prepare("UPDATE subscription SET Wishlist = ? WHERE Visitor_ID = ?");
        $stmt->execute([$newWishlist, $userId]);

        $msg = "Saved! Added to your Up Next / Watchlist.";
        $isOk = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Choose Up Next</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="container">
  <div class="card" style="max-width: 620px;">
    <h1>Choose Up Next</h1>
    <p class="sub">Select an upcoming movie and save it to your Watchlist</p>

    <?php if ($msg): ?>
      <div class="msg <?= $isOk ? "ok" : "error" ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="up_next.php">
      <label>Upcoming Movies</label>
      <select name="up_movie" class="select" required>
        <option value="">-- Select a movie --</option>
        <?php foreach ($upcoming as $m): ?>
          <option value="<?= h((string)$m) ?>"><?= h((string)$m) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Save Up Next</button>
    </form>

    <h3 style="margin-top:18px;">Your Watchlist</h3>
    <ul style="margin:0; padding-left:18px;">
      <?php if (empty($watchlist)): ?>
        <li>No movies saved yet.</li>
      <?php else: ?>
        <?php foreach ($watchlist as $m): ?>
          <li><?= h($m) ?></li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>

    <div class="row" style="margin-top:16px;">
      <a class="btn" href="movie_catalogue.php">Movie Catalogue</a>
      <a class="btn" href="dashboard.php">Back</a>
    </div>
  </div>
</div>
</body>
</html>
