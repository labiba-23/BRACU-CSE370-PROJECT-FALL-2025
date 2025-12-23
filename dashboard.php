<?php

declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$stmt = $pdo->prepare("
  SELECT COUNT(*) 
  FROM friend_requests
  WHERE receiver_id = ? AND status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$pendingCount = (int)$stmt->fetchColumn();


$name = $_SESSION["user_name"] ?? "User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard - TheatreFlix</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">

    <div class="app-topbar">
      <div class="app-brand">THEATRE<span>FLIX</span></div>
      <div class="app-nav">
        <span class="app-chip">Hi! <?= htmlspecialchars((string)$name) ?></span>

        <a href="friend_request.php" class="notif-btn" 
          
          <?php if ($pendingCount > 0): ?>
            <span class="notif-badge"><?= $pendingCount ?></span>
          <?php endif; ?>
        </a>
        <a href="movie_catalogue.php">Catalogue</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>

    <div class="app-card">
      <div class="app-title">Dashboard</div>
      <div class="app-sub">Choose what you want to do next!</div>

      <div class="app-grid">
        <a class="btn" href="up_next.php">Choose Up Next</a>
        <a class="btn btn-ghost" href="polls.php">Polls</a>
        <a class="btn btn-ghost" href="profile.php">Profile</a>
        <a class="btn btn-ghost" href="watchlist.php">Watchlist</a>
        <a class="btn btn-ghost" href="friends.php">Friends</a>
<a class="btn btn-ghost" href="friend_request.php">Friend Requests</a>

      </div>
    </div>

  </div>
</body>
</html>
<?php
