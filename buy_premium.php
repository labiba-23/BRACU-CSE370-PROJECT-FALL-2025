<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


$stmt = $pdo->prepare("SELECT is_premium, premium_until FROM visitor WHERE ID = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$isPremium = (int)($user["is_premium"] ?? 0) === 1;
$premiumUntil = (string)($user["premium_until"] ?? "");

$msg = "";
$isOk = true;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT balance FROM visitor WHERE ID=? FOR UPDATE");
        $stmt->execute([$userId]);
        $balance = (int)$stmt->fetchColumn();

        $price = 500; // 500 BDT
        if ($balance < $price) {
            throw new Exception("Not enough balance. Need {$price} BDT.");
        }

       
        $stmt = $pdo->prepare("UPDATE visitor SET balance = balance - ? WHERE ID=?");
        $stmt->execute([$price, $userId]);

        $stmt = $pdo->prepare("
            UPDATE visitor
            SET is_premium = 1,
                premium_until = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            WHERE ID=?
        ");
        $stmt->execute([$userId]);

       
        $stmt = $pdo->prepare("
            INSERT INTO balance_ledger (visitor_id, party_id, change_amount, reason)
            VALUES (?, NULL, ?, ?)
        ");
        $stmt->execute([$userId, -$price, "Bought Premium (30 days)"]);

        $pdo->commit();

        header("Location: dashboard.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = $e->getMessage();
        $isOk = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Buy Premium</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>
<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title">Premium Membership</div>
      <div class="app-sub">Unlock premium discounts in shop</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <?php if ($isPremium): ?>
        <div class="notice ok">
          <?php if ($premiumUntil): ?>
            (valid until <?= h($premiumUntil) ?>)
          <?php endif; ?>
        </div>
        <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>

      <?php else: ?>
        <div style="margin-top:12px; font-weight:800;">
          Price: <b>500 BDT</b> (30 days)
        </div>

        <form method="POST" style="margin-top:14px;">
          <button type="submit" class="btn">Buy Premium</button>
        </form>

        <div style="margin-top:14px;">
          <a class="btn btn-ghost" href="balance.php">Go to Wallet</a>
          <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</body>
</html>
