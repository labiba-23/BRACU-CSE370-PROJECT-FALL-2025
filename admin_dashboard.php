<?php
// admin_dashboard.php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

// 1) Must be logged in (login.php sets these)
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$name   = $_SESSION["user_name"] ?? "Admin";

// 2) Confirm this user is ADMIN using your admin table
$stmt = $pdo->prepare("SELECT `Add polls` AS add_polls FROM `admin` WHERE ID = ? LIMIT 1");
$stmt->execute([$userId]);
$adminRow = $stmt->fetch();

if (!$adminRow) {
    // Not an admin -> send to visitor dashboard
    header("Location: dashboard.php");
    exit;
}

$addPollsStatus = (string)($adminRow["add_polls"] ?? ""); // e.g., "okay"

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <div class="container">
    <div class="card" style="max-width: 560px;">
      <h1>Admin Dashboard</h1>
      <p class="sub">Welcome, <?= h($name) ?> 👑</p>

      <div class="profile-grid" style="grid-template-columns: 170px 1fr;">
        <div><b>Admin ID:</b></div><div><?= $userId ?></div>
        <div><b>Add Polls Permission:</b></div><div><?= h($addPollsStatus) ?></div>
      </div>

      <div class="menu" style="margin-top: 16px;">
        <!-- These are links to pages you will create next -->
        <a class="btn" href="admin_polls.php">Manage Polls</a>
        <a class="btn" href="admin_movies.php">Manage Movies</a>
        <a class="btn" href="admin_shows.php">Manage Shows</a>
        <a class="btn" href="admin_users.php">View Users</a>
        <a class="btn" href="admin_movies.php">Manage Movies</a>
<a class="btn" href="movie_catalogue.php">Movie Catalogue</a>

      </div>

      <div class="link" style="margin-top: 16px;">
        <a href="dashboard.php">Go to Visitor Dashboard</a> |
        <a href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</body>
</html>
