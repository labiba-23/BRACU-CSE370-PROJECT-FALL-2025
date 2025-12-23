<?php  //I am adding movie management features
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];


$stmt = $pdo->prepare("SELECT ID FROM admin WHERE ID = ? LIMIT 1");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
  header("Location: dashboard.php");
  exit;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$msg = "";
$isOk = true;

// DELETE movie 
if (isset($_GET["delete"])) {
  $releasedYear = $_GET["delete"]; // yyyy-mm-dd

  $pdo->beginTransaction();
  try {
 
    $stmt = $pdo->prepare("DELETE FROM movie_loc WHERE Released_year = ?");
    $stmt->execute([$releasedYear]);

    $stmt = $pdo->prepare("DELETE FROM add_remove WHERE released_year = ?");
    $stmt->execute([$releasedYear]);

    $stmt = $pdo->prepare("DELETE FROM movie_catalogue WHERE released_year = ?");
    $stmt->execute([$releasedYear]);


    $pdo->commit();
    $msg = "Movie removed successfully.";
    $isOk = true;
  } catch (Exception $e) {
    $pdo->rollBack();
    $msg = "Remove failed: " . $e->getMessage();
    $isOk = false;
  }
}

// ADD movie 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $released_year = trim($_POST["released_year"] ?? ""); // date
  $on_movie      = trim($_POST["on_movie"] ?? "");
  $up_movie      = trim($_POST["up_movie"] ?? "");
  $duration      = (int)($_POST["duration"] ?? 0);
  $reviews       = trim($_POST["reviews"] ?? "");
  $celeb_blog    = trim($_POST["celeb_blog"] ?? "");


  if ($released_year === "" || ($on_movie === "" && $up_movie === "")) {
    $msg = "Released year is required and you must fill Ongoing OR Upcoming movie name.";
    $isOk = false;
  } else {
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("
        INSERT INTO movie_catalogue (celeb_blog, released_year, reviews, up_movie, on_movie, duration)
        VALUES (?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$celeb_blog, $released_year, $reviews, $up_movie, $on_movie, $duration]);

      $stmt = $pdo->prepare("INSERT IGNORE INTO add_remove (ID, released_year) VALUES (?, ?)");
      $stmt->execute([$userId, $released_year]);

      $pdo->commit();
      $msg = "Movie added successfully.";
      $isOk = true;
    } catch (Exception $e) {
      $pdo->rollBack();
      $msg = "Add failed: " . $e->getMessage();
      $isOk = false;
    }
  }
}

// latest movies
$rows = $pdo->query("SELECT * FROM movie_catalogue ORDER BY released_year DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Movies</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="container">
  <div class="card" style="max-width: 980px;">
    <h1>Manage Movies</h1>
    <p class="sub">Add / Remove movies using your movie_catalogue table</p>

    <?php if ($msg): ?>
      <div class="msg <?= $isOk ? "ok" : "error" ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <h3 style="margin-top:10px;">Add Movie</h3>
    <form method="POST" action="admin_movies.php" autocomplete="off">
      <label>Released Year (date)</label>
      <input type="date" name="released_year" required>

      <label>Ongoing Movie Name (on_movie)</label>
      <input type="text" name="on_movie" placeholder="Fill this if movie is ongoing">

      <label>Upcoming Movie Name (up_movie)</label>
      <input type="text" name="up_movie" placeholder="Fill this if movie is upcoming">

      <label>Duration (minutes)</label>
      <input type="number" name="duration" min="0" placeholder="e.g., 120">

      <label>Reviews</label>
      <input type="text" name="reviews" placeholder="e.g., 4.5/5">

      <label>Celeb Blog</label>
      <input type="text" name="celeb_blog" placeholder="optional">

      <button type="submit">Add Movie</button>
    </form>

    <h3 style="margin-top:18px;">Current Movies in Table</h3>
    <div style="overflow:auto; border:1px solid #f5b7c7; border-radius:12px; background: rgba(255,255,255,0.6); padding:10px;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #f5b7c7;">released_year</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #f5b7c7;">on_movie</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #f5b7c7;">up_movie</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #f5b7c7;">duration</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #f5b7c7;">reviews</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #f5b7c7;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="6" style="padding:8px;">No movies found.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td style="padding:8px; border-bottom:1px solid #f5b7c7;"><?= h((string)$r["released_year"]) ?></td>
                <td style="padding:8px; border-bottom:1px solid #f5b7c7;"><?= h((string)$r["on_movie"]) ?></td>
                <td style="padding:8px; border-bottom:1px solid #f5b7c7;"><?= h((string)$r["up_movie"]) ?></td>
                <td style="padding:8px; border-bottom:1px solid #f5b7c7;"><?= h((string)$r["duration"]) ?></td>
                <td style="padding:8px; border-bottom:1px solid #f5b7c7;"><?= h((string)$r["reviews"]) ?></td>
                <td style="padding:8px; border-bottom:1px solid #f5b7c7;">
                  <a href="admin_movies.php?delete=<?= urlencode((string)$r["released_year"]) ?>"
                     onclick="return confirm('Remove this movie entry?');">Remove</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="row" style="margin-top:16px;">
      <a class="btn" href="movie_catalogue.php">Movie Catalogue</a>
      <a class="btn" href="admin_dashboard.php">Back</a>
    </div>

    <div class="link">
      <a href="logout.php">Logout</a>
    </div>
  </div>
</div>
</body>
</html>
<?php

