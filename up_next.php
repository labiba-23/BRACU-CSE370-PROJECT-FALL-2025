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
  $pdo->prepare("INSERT INTO visitor (ID, Reward_points, Privacy) VALUES (?, 0, 0)")
      ->execute([$userId]);
}


$stmt = $pdo->prepare("SELECT Wishlist FROM subscription WHERE Visitor_ID = ? LIMIT 1");
$stmt->execute([$userId]);
$sub = $stmt->fetch();

if (!$sub) {
  $sId = random_int(100000, 999999);
  $pdo->prepare("INSERT INTO subscription (S_ID, Subtitles, Wishlist, Visitor_ID) VALUES (?, ?, ?, ?)")
      ->execute([$sId, "", "", $userId]);
  $wishlistStr = "";
} else {
  $wishlistStr = (string)($sub["Wishlist"] ?? "");
}

$watchlist = [];
if (trim($wishlistStr) !== "") {
  $watchlist = array_values(array_filter(array_map("trim", explode(",", $wishlistStr))));
}


$stmt = $pdo->query("
  SELECT released_year, up_movie, duration, reviews, poster
  FROM movie_catalogue
  WHERE up_movie IS NOT NULL AND TRIM(up_movie) <> ''
  ORDER BY released_year ASC
");
$upcomingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_up_next") {
  $selected = trim((string)($_POST["movie"] ?? ""));

  if ($selected === "") {
    $msg = "Please select a movie.";
    $isOk = false;
  } else {
   
    $exists = false;
    foreach ($upcomingRows as $r) {
      if ((string)$r["up_movie"] === $selected) { $exists = true; break; }
    }

    if (!$exists) {
      $msg = "Invalid selection.";
      $isOk = false;
    } else {
      if (!in_array($selected, $watchlist, true)) {
        $watchlist[] = $selected;
      }

      $newWishlist = implode(", ", $watchlist);
      $stmt = $pdo->prepare("UPDATE subscription SET Wishlist = ? WHERE Visitor_ID = ?");
      $stmt->execute([$newWishlist, $userId]);

      $msg = "Saved! Added to your Watchlist.";
      $isOk = true;
    }
  }
}

function posterPath(array $row): string {
  $p = trim((string)($row["poster"] ?? ""));
  return $p !== "" ? $p : "assets/placeholder.jpg";
}
function yearText($released_year): string {
  $s = (string)$released_year;
  return (strlen($s) >= 4) ? substr($s, 0, 4) : $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Choose Up Next</title>
  <link rel="stylesheet" href="style.css"/>

 
  <style>
    .upnext-grid{
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap:14px;
      margin-top:14px;
    }
    .movie-card{
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 16px;
      overflow:hidden;
      box-shadow:0 12px 30px rgba(0,0,0,.35);
      transition:transform .15s ease, border-color .15s ease;
    }
    .movie-card:hover{
      transform:translateY(-3px);
      border-color: rgba(255,79,163,0.55);
    }
    .movie-poster{
      height:240px;
      position:relative;
      background: linear-gradient(135deg, rgba(255,79,163,0.25), rgba(122,167,255,0.18));
    }
    .movie-poster img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .badge-soon{
      position:absolute;
      top:10px;
      right:10px;
      background:rgba(255,255,255,0.12);
      border:1px solid rgba(255,255,255,0.18);
      padding:6px 8px;
      border-radius:10px;
      font-size:12px;
      font-weight:900;
      letter-spacing:0.2px;
      color:#fff;
    }
    .movie-meta{
      padding:12px;
    }
    .movie-title{
      font-weight:900;
      font-size:16px;
      margin:0 0 6px;
      color:#fff;
    }
    .movie-meta small{
      display:block;
      color:rgba(255,255,255,0.75);
      margin-top:4px;
      font-size:12px;
    }
    .saveBtn{
      width:100%;
      border:0;
      cursor:pointer;
      border-radius:12px;
      padding:12px 10px;
      font-weight:900;
      margin-top:10px;
      background:#ff4fa3;
      color:#120612;
    }
    .saveBtn:hover{ filter:brightness(.96); }
  </style>
</head>

<body>
<div class="container">
  <div class="card" style="max-width: 980px;">
    <h1>Choose Up Next</h1>
    <p class="sub">Select an upcoming movie and save it to your Watchlist</p>

    <?php if ($msg): ?>
      <div class="msg <?= $isOk ? "ok" : "error" ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <?php if (!$upcomingRows): ?>
      <p class="sub">No upcoming movies found.</p>
    <?php else: ?>
      <div class="upnext-grid">
        <?php foreach ($upcomingRows as $m): ?>
          <?php
            $title = (string)$m["up_movie"];
            $poster = posterPath($m);
          ?>
          <div class="movie-card">
            <div class="movie-poster">
              <img
                src="<?= h($poster) ?>"
                alt="<?= h($title) ?>"
                onerror="this.onerror=null;this.src='assets/placeholder.jpg';"
              >
              <div class="badge-soon">SOON</div>
            </div>

            <div class="movie-meta">
              <div class="movie-title"><?= h($title) ?></div>
              <small>Release: <?= h((string)$m["released_year"]) ?> • Duration: <?= h((string)$m["duration"]) ?> min</small>
              <small>Reviews: <?= h((string)$m["reviews"]) ?></small>

              <form method="POST" action="up_next.php">
                <input type="hidden" name="action" value="add_up_next">
                <input type="hidden" name="movie" value="<?= h($title) ?>">
                <button class="saveBtn" type="submit">Save Up Next</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

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
