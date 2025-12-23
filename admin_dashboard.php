<?php

declare(strict_types=1);
require_once __DIR__ . "/config.php";


if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$name   = $_SESSION["user_name"] ?? "Admin";


$stmt = $pdo->prepare("SELECT `Add polls` AS add_polls FROM `admin` WHERE ID = ? LIMIT 1");
$stmt->execute([$userId]);
$adminRow = $stmt->fetch();

if (!$adminRow) {
    
    header("Location: dashboard.php");
    exit;
}

$addPollsStatus = (string)($adminRow["add_polls"] ?? ""); 

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard - TheatreFlix</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">

    <div class="app-topbar">
      <div class="app-brand">THEATRE<span>FLIX</span> <span style="opacity:.75;font-weight:900;">Admin</span></div>
      <div class="app-nav">
        <span class="app-chip">Hi, <?= htmlspecialchars((string)$name) ?></span>
        <a href="movie_catalogue.php">Catalogue</a>
        <a href="dashboard.php">Visitor View</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>

    <div class="app-card">
      <div class="app-title">Admin Dashboard</div>
      <div class="app-sub">Manage movies, polls and users.</div>

      <div class="app-grid">
        <a class="btn" href="admin_movies.php">Manage Movies</a>
        <a class="btn btn-ghost" href="admin_polls.php">Manage Polls</a>
        <a class="btn btn-ghost" href="admin_users.php">View Users</a>
      </div>
    </div>

  </div>
</body>
</html>
<?php
