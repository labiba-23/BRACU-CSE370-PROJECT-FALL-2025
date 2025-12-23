<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$guest = !isset($_SESSION["user_id"]);
$userId = $guest ? null : (int)$_SESSION["user_id"];
$name   = $guest ? "Guest" : (string)($_SESSION["user_name"] ?? "User");
$name = $_SESSION["user_name"] ?? "User";

$watchlist = [];
$msg = "";


if (!$guest && $userId !== null) {

  
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

  if (trim($wishlistStr) !== "") {
    $watchlist = array_values(array_filter(array_map("trim", explode(",", $wishlistStr))));
  }

  
  if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_up_next") {
    $movie = trim((string)($_POST["movie"] ?? ""));
    if ($movie !== "") {
      if (!in_array($movie, $watchlist, true)) $watchlist[] = $movie;
      $newWishlist = implode(", ", $watchlist);
      $pdo->prepare("UPDATE subscription SET Wishlist = ? WHERE Visitor_ID = ?")
          ->execute([$newWishlist, $userId]);
      $msg = "Added to your Up Next list!";
    }
  }
}


$stmt = $pdo->query("
  SELECT released_year, on_movie, up_movie, duration, reviews, celeb_blog
  FROM movie_catalogue
  WHERE on_movie IS NOT NULL AND TRIM(on_movie) <> ''
  ORDER BY released_year DESC
");
$ongoing = $stmt->fetchAll();


$stmt = $pdo->query("
  SELECT released_year, on_movie, up_movie, duration, reviews, celeb_blog
  FROM movie_catalogue
  WHERE up_movie IS NOT NULL AND TRIM(up_movie) <> ''
  ORDER BY released_year ASC
");
$upcoming = $stmt->fetchAll();


function posterUrl(?string $celeb_blog): ?string {
  if (!$celeb_blog) return null;
  $s = trim($celeb_blog);
  $lower = strtolower($s);
  if (preg_match('/\.(jpg|jpeg|png|webp)$/', $lower)) return $s;
  return null;
}

function yearText($released_year): string {
  $s = (string)$released_year;
  if (strlen($s) >= 4) return substr($s, 0, 4);
  return $s;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Movie Catalogue</title>

  
  <style>
    *{ box-sizing:border-box; }
    body{
      margin:0;
      font-family: Arial, Helvetica, sans-serif;
      background:#0b0b0f;
      color:#ffffff;
    }
    .topbar{
      position:sticky;
      top:0;
      z-index:10;
      background:rgba(11,11,15,0.9);
      backdrop-filter: blur(8px);
      border-bottom:1px solid rgba(255,255,255,0.08);
      padding:14px 18px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
    }
    .brand{
      font-weight:800;
      letter-spacing:0.5px;
      font-size:18px;
    }
    .brand span{ color:#ff4fa3; }
    .nav a{
      color:#cfd2ff;
      text-decoration:none;
      margin-left:12px;
      font-weight:600;
      font-size:14px;
    }
    .nav a:hover{ text-decoration:underline; }

    .wrap{
      padding:18px;
      max-width:1200px;
      margin:0 auto;
    }

    .msg{
      background:rgba(60, 200, 120, 0.15);
      border:1px solid rgba(60, 200, 120, 0.35);
      padding:10px 12px;
      border-radius:12px;
      margin:12px 0;
      color:#d9ffe8;
    }

    h1{
      margin:10px 0 2px;
      font-size:28px;
      letter-spacing:0.3px;
    }
    .sub{
      margin:0 0 14px;
      color:rgba(255,255,255,0.7);
      font-size:14px;
    }

    .section-head{
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:10px;
      margin-top:18px;
    }
    .section-title{
      font-size:20px;
      font-weight:800;
      margin:0;
    }
    .seeall{
      color:#7aa7ff;
      font-weight:700;
      font-size:13px;
      text-decoration:none;
    }
    .seeall:hover{ text-decoration:underline; }

    .row{
      display:flex;
      gap:14px;
      overflow-x:auto;
      padding:12px 4px 10px;
      scroll-snap-type: x mandatory;
    }
    .row::-webkit-scrollbar{ height:10px; }
    .row::-webkit-scrollbar-thumb{ background:rgba(255,255,255,0.18); border-radius:12px; }
    .row::-webkit-scrollbar-track{ background:rgba(255,255,255,0.05); border-radius:12px; }

    .card{
      width:210px;
      flex:0 0 auto;
      scroll-snap-align:start;
      border-radius:16px;
      overflow:hidden;
      background:rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      box-shadow: 0 12px 30px rgba(0,0,0,0.55);
      transition: transform .15s ease, border-color .15s ease;
    }
    .card:hover{
      transform: translateY(-3px);
      border-color: rgba(255,79,163,0.55);
    }

    .poster{
      height:280px;
      position:relative;
      background: linear-gradient(135deg, rgba(255,79,163,0.25), rgba(122,167,255,0.18));
      display:flex;
      align-items:flex-end;
      justify-content:flex-start;
      padding:12px;
    }
    .poster img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
      position:absolute;
      inset:0;
    }
    .badge{
      position:absolute;
      top:10px;
      right:10px;
      background:rgba(255,255,255,0.12);
      border:1px solid rgba(255,255,255,0.18);
      padding:6px 8px;
      border-radius:10px;
      font-size:12px;
      font-weight:800;
      letter-spacing:0.2px;
    }
    .title-shadow{
      position:relative;
      z-index:2;
      font-weight:800;
      font-size:15px;
      line-height:1.15;
      text-shadow: 0 10px 20px rgba(0,0,0,0.9);
      max-width:100%;
    }

    .meta{
      padding:10px 12px 12px;
    }
    .meta small{
      display:block;
      color:rgba(255,255,255,0.72);
      margin-top:4px;
      font-size:12px;
    }

    .actions{
      display:flex;
      gap:8px;
      margin-top:10px;
    }
    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      border:0;
      cursor:pointer;
      border-radius:12px;
      padding:10px 10px;
      font-weight:800;
      font-size:13px;
      text-decoration:none;
      width:100%;
    }
    .btn-pink{
      background:#ff4fa3;
      color:#120612;
    }
    .btn-pink:hover{ filter: brightness(0.95); }
    .btn-ghost{
      background:rgba(255,255,255,0.08);
      color:#fff;
      border:1px solid rgba(255,255,255,0.12);
    }
    .btn-ghost:hover{ border-color: rgba(255,79,163,0.55); }

    .pill{
      display:inline-block;
      padding:6px 10px;
      border-radius:999px;
      background:rgba(255,255,255,0.08);
      border:1px solid rgba(255,255,255,0.12);
      font-size:12px;
      color:rgba(255,255,255,0.8);
      margin-right:8px;
    }

    .listbox{
      margin-top:18px;
      background:rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      border-radius:16px;
      padding:14px;
    }
    .listbox h3{ margin:0 0 10px; }
    .list{
      margin:0;
      padding-left:18px;
      color:rgba(255,255,255,0.86);
    }
  </style>
</head>

<body>
  <div class="topbar">
    <div class="brand">THEATRE<span>FLIX</span></div>
<div class="nav">
  <span style="color:rgba(255,255,255,0.7); font-weight:700; font-size:13px;">
    Hi, <?= h($name) ?>
  </span>

  <?php if ($guest): ?>
    <a href="login.php">Sign in</a>
    <a href="register.php">Create account</a>
  <?php else: ?>
    <a href="dashboard.php">Dashboard</a>
    <a href="watchlist.php">Watchlist</a>
    <a href="logout.php">Logout</a>
  <?php endif; ?>
</div>

  </div>

  <div class="wrap">
    <h1>Movies in Theatres</h1>
    <p class="sub">Ongoing & Coming Soon — select what you want to watch up next</p>

    <?php if ($msg): ?>
      <div class="msg"><?= h($msg) ?></div>
    <?php endif; ?>

    <div class="section-head">
      <h2 class="section-title">🎬 Now Playing</h2>
      <a class="seeall" href="#now">SEE ALL MOVIES</a>
    </div>

    <div class="row" id="now">
      <?php if (!$ongoing): ?>
        <div class="pill">No ongoing movies yet</div>
      <?php else: ?>
        <?php foreach ($ongoing as $m): ?>
          <?php
            $title = (string)$m["on_movie"];
            $poster = posterUrl($m["celeb_blog"] ?? null);
          ?>
          <div class="card">
            <div class="poster">
              <?php if ($poster): ?>
                <img src="<?= h($poster) ?>" alt="<?= h($title) ?>">
              <?php endif; ?>
              <div class="badge">NOW</div>
              <div class="title-shadow"><?= h($title) ?></div>
            </div>
            <div class="meta">
              <small>Year: <?= h(yearText($m["released_year"])) ?> • Duration: <?= h((string)$m["duration"]) ?> min</small>
              <small>Reviews: <?= h((string)$m["reviews"]) ?></small>

              <div class="actions">
                <form method="POST" style="width:100%;">
                  <input type="hidden" name="action" value="add_up_next">
                  <input type="hidden" name="movie" value="<?= h($title) ?>">
                  <button class="btn btn-pink" type="submit">
  ➕ Add to Watchlist
</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="section-head" style="margin-top:22px;">
      <h2 class="section-title">📅 Coming Soon to Theatres</h2>
      <a class="seeall" href="#soon">SEE ALL MOVIES</a>
    </div>

    <div class="row" id="soon">
      <?php if (!$upcoming): ?>
        <div class="pill">No upcoming movies yet</div>
      <?php else: ?>
        <?php foreach ($upcoming as $m): ?>
          <?php
            $title = (string)$m["up_movie"];
            $poster = posterUrl($m["celeb_blog"] ?? null);
          ?>
          <div class="card">
            <div class="poster">
              <?php if ($poster): ?>
                <img src="<?= h($poster) ?>" alt="<?= h($title) ?>">
              <?php endif; ?>
              <div class="badge">SOON</div>
              <div class="title-shadow"><?= h($title) ?></div>
            </div>
            <div class="meta">
              <small>Release: <?= h((string)$m["released_year"]) ?> • Duration: <?= h((string)$m["duration"]) ?> min</small>
              <small>Reviews: <?= h((string)$m["reviews"]) ?></small>

              <div class="actions">
                <form method="POST" style="width:100%;">
                  <input type="hidden" name="action" value="add_up_next">
                  <input type="hidden" name="movie" value="<?= h($title) ?>">
                  <button class="btn btn-pink" type="submit">Add to Up Next</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="listbox">
      <h3>Your Up Next / Watchlist</h3>
      <?php if (!$watchlist): ?>
        <div class="pill">No selections yet. Click “Add to Up Next” on a movie.</div>
      <?php else: ?>
        <ul class="list">
          <?php foreach ($watchlist as $w): ?>
            <li><?= h($w) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>

