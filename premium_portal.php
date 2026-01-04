<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


$stmt = $pdo->prepare("SELECT is_premium, premium_until FROM visitor WHERE ID=?");
$stmt->execute([$userId]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

$isPremium = $u && (int)$u["is_premium"] === 1;
$until = $u ? (string)($u["premium_until"] ?? "") : "";

if (!$isPremium) {
  header("Location: buy_premium.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Premium Portal</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>
<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title">Premium Portal</div>
      <div class="app-sub">Welcome! Premium active until: <?= h($until ?: "N/A") ?></div>

    
      <div class="row">
        <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
       
      </div>

      <hr style="margin:18px 0; opacity:.2">

      <p class="app-sub">
        Create <b>premium_upload.php</b> next, then we’ll store links in DB and display them here.
      </p>
    </div>
  </div>
</body>
</html>
