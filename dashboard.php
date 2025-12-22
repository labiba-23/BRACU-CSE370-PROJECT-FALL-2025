<?php
// dashboard.php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION["user_name"] ?? "User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard - Theatre</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <div class="container">
    <div class="card">
   <h1>Hello, <?= htmlspecialchars($name) ?> 👋</h1>
<p class="sub">Welcome to your movie portal</p>

<div class="menu">
  <a class="btn" href="profile.php">Profile</a>
  <a class="btn" href="friends.php">Friends</a>
  <a class="btn" href="watchlist.php">Watchlist</a>
  <a class="btn" href="movie_catalogue.php">Movie Catalogue</a>
<a class="btn" href="up_next.php">Choose Up Next</a>
<a class="btn" href="polls.php">Polls</a>


</div>

<div class="link">
  <a href="logout.php">Logout</a>
</div>

    </div>
  </div>
</body>
</html>
