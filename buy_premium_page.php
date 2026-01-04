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

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
        LIMIT 1
    ");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

$msg = "";
$isOk = true;

$price = 500;
$days  = 30;

$isPremium = isPremium($pdo, $userId);


$stmt = $pdo->prepare("SELECT balance FROM visitor WHERE ID = ?");
$stmt->execute([$userId]);
$balance = (int)($stmt->fetchColumn() ?? 0);


if ($_SERVER["REQUEST_METHOD"] === "POST" && !$isPremium) {
    try {
        $pdo->beginTransaction();

      
        $stmt = $pdo->prepare("SELECT balance FROM visitor WHERE ID=? FOR UPDATE");
        $stmt->execute([$userId]);
        $balNow = (int)$stmt->fetchColumn();

        if ($balNow < $price) {
            throw new Exception("Not enough balance to buy Premium.");
        }

  
        $stmt = $pdo->prepare("UPDATE visitor SET balance = balance - ? WHERE ID=?");
        $stmt->execute([$price, $userId]);

       
        $until = (new DateTimeImmutable("now"))->modify("+{$days} days")->format("Y-m-d");

      
        $stmt = $pdo->prepare("
            UPDATE visitor
            SET is_premium = 1,
                premium_until = ?
            WHERE ID = ?
        ");
        $stmt->execute([$until, $userId]);

       
        if (tableExists($pdo, "balance_ledger")) {
            $stmt = $pdo->prepare("
                INSERT INTO balance_ledger (visitor_id, party_id, change_amount, reason)
                VALUES (?, NULL, ?, ?)
            ");
            $stmt->execute([$userId, -$price, "Premium subscription ({$days} days)"]);
        }

        $pdo->commit();

        header("Location: premium_movies.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = $e->getMessage();
        $isOk = false;
    }

    // refresh view values
    $isPremium = isPremium($pdo, $userId);
    $stmt = $pdo->prepare("SELECT balance FROM visitor WHERE ID = ?");
    $stmt->execute([$userId]);
    $balance = (int)($stmt->fetchColumn() ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Buy Subscription - TheatreFlix</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">

    <div class="app-topbar">
      <div class="app-brand">THEATRE<span>FLIX</span></div>
      <div class="app-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="shop.php">Shop</a>
        <a href="balance.php">Wallet</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>

    <div class="app-card">
      <div class="app-title pink">Premium Membership</div>
      <div class="app-sub">
        Unlock <b>Premium Movies</b> + get <b>10% discount</b> on shop checkout.
      </div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <div class="notice">
        <b>Price:</b> <?= $price ?> BDT &nbsp; • &nbsp; <b>Duration:</b> <?= $days ?> days<br>
        <b>Your wallet balance:</b> <?= $balance ?> BDT
      </div>

      <?php if ($isPremium): ?>
        <div class="notice ok">You already have an active Premium subscription ✅</div>
        <div class="row">
          <a class="btn" href="premium_portal.php">Go to Premium Portal</a>
          <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
        </div>
      <?php else: ?>
        <?php if ($balance < $price): ?>
          <div class="notice err">Not enough balance to buy Premium. Add balance first.</div>
          <div class="row">
            <a class="btn" href="balance.php">Go to Wallet</a>
            <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
          </div>
        <?php else: ?>
          <form method="POST" action="">
            <button class="btn" type="submit">Buy Premium</button>
            <a class="btn btn-ghost" href="dashboard.php">Cancel</a>
          </form>
        <?php endif; ?>
      <?php endif; ?>

    </div>

  </div>
</body>
</html>

