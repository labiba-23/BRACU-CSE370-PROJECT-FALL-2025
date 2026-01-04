<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/premium_helper.php"; 

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$isPremium = isPremium($pdo, $userId); 


$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM friend_requests
  WHERE receiver_id = ? AND status = 'pending'
");
$stmt->execute([$userId]);
$pendingCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM party_invites
  WHERE visitor_id = ? AND status = 'pending'
");
$stmt->execute([$userId]);
$partyPendingCount = (int)$stmt->fetchColumn();


$bellClassFriends = ($pendingCount > 0) ? "" : "no-notif";
$bellClassParty   = ($partyPendingCount > 0) ? "" : "no-notif";

$name = $_SESSION["user_name"] ?? "User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard - TheatreFlix</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="style.css?v=999">

  <style>
   
    .notif-btn.no-notif { opacity: 0.55; }
    .notif-btn.no-notif:hover { opacity: 0.85; }

    .dashboard-actions {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 18px;
      margin-top: 18px;
    }

    .action-card {
      background: linear-gradient(135deg, #f08ac1, #e06ba8);
      color: #111;
      padding: 22px;
      border-radius: 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      text-decoration: none;
      font-weight: 700;
      box-shadow: 0 12px 30px rgba(0,0,0,.25);
      transition: transform .2s ease, box-shadow .2s ease;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      min-height: 110px;
    }

    .action-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 18px 40px rgba(0,0,0,.35);
    }

    .action-card .icon {
      font-size: 32px;
      line-height: 1;
    }

    .action-card.primary {
      background: linear-gradient(135deg, #ff77b7, #ff4fa0);
      color: #000;
    }

    .premium-badge {
      display:inline-block;
      margin-left:10px;
      padding:6px 10px;
      border-radius:999px;
      font-size:12px;
      font-weight:900;
      border:1px solid rgba(255,255,255,.25);
      background: rgba(255,255,255,.10);
    }
  </style>
</head>

<body class="app-body">
  <div class="app-page">

    
    <div class="app-topbar">
      <div class="app-brand">THEATRE<span>FLIX</span></div>

      <div class="app-nav">
        <span class="app-chip">
          Hi! <?= htmlspecialchars($name) ?>
          <?php if ($isPremium): ?>
            <span class="premium-badge">Premium ✅</span>
          <?php endif; ?>
        </span>

       
        <a href="friend_request.php"
           class="notif-btn <?= $bellClassFriends ?>"
           title="Friend Requests">
          🔔
          <?php if ($pendingCount > 0): ?>
            <span class="notif-badge"><?= $pendingCount ?></span>
          <?php endif; ?>
        </a>

       
        <a href="balance.php"
           class="notif-btn <?= $bellClassParty ?>"
           title="Watch Party Invites">
          🎬
          <?php if ($partyPendingCount > 0): ?>
            <span class="notif-badge"><?= $partyPendingCount ?></span>
          <?php endif; ?>
        </a>

        <a href="purchase_history.php">My Purchases</a>
        <a href="movie_catalogue.php">Catalogue</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>

    
    <div class="app-card">
      <div class="app-title">Dashboard</div>
      <div class="app-sub">Choose what you want to do next!</div>

      <div class="dashboard-actions">

        <a class="action-card" href="up_next.php">
          <span class="icon">🎬</span>
          Choose UpNext!
        </a>

        <a class="action-card" href="polls.php">
          <span class="icon">📊</span>
          Polls
        </a>

        <a class="action-card" href="profile.php">
          <span class="icon">👤</span>
          Profile
        </a>

        <a class="action-card" href="friends.php">
          <span class="icon">👥</span>
          Friends & Groups
        </a>

        <a class="action-card primary" href="start_party.php">
          <span class="icon">🎉</span>
          Start Watch Party
        </a>

       
        <?php if ($isPremium): ?>
          <a class="action-card primary" href="premium_movies.php">
            <span class="icon">⭐</span>
            Premium Movies
          </a>
        <?php else: ?>
          <a class="action-card primary" href="buy_premium_page.php">
            <span class="icon">⭐</span>
            Buy Subscription
          </a>
        <?php endif; ?>

        <a class="action-card" href="shop.php">
          <span class="icon">🛍️</span>
          Shop
        </a>

        <a class="action-card" href="balance.php">
          <span class="icon">💰</span>
          Balance
        </a>

        <a class="action-card" href="games.php">
          <span class="icon">🎮</span>
          Games
        </a>

      </div>
    </div>

  </div>
</body>
</html>




