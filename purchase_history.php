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

$stmt = $pdo->prepare("
  SELECT p.P_ID, p.P_date, p.Amount, p.points_used, p.wallet_used, p.paid_amount
  FROM purchase p
  WHERE p.Visitor_ID = ?
  ORDER BY p.P_ID DESC
  LIMIT 50
");
$stmt->execute([$userId]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase History</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">

      <div class="app-title">Purchase History</div>
      <div class="app-sub">Your recent purchases</div>

      <hr style="margin:18px 0; opacity:.2">

      <?php if (!$list): ?>
        <p class="app-sub">No purchases found.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>P_ID</th>
                <th>Date</th>
                <th>Total</th>
                <th>Points</th>
                <th>Balance</th>
                <th>To Pay</th>
                <th>View</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($list as $p): ?>
                <tr>
                  <td><?= (int)$p["P_ID"]; ?></td>
                  <td><?= h((string)$p["P_date"]); ?></td>
                  <td><?= (int)$p["Amount"]; ?></td>
                  <td><?= (int)$p["points_used"]; ?></td>
                  <td><?= (int)$p["wallet_used"]; ?></td>
                  <td><?= (int)$p["paid_amount"]; ?></td>
                  <td>
                    <a class="btn btn-ghost small" href="purchase_success.php?pid=<?= (int)$p["P_ID"]; ?>">
                      Open
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="shop.php">Go to Shop</a>
        <a class="btn btn-ghost" href="balance.php">Wallet</a>
        <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
      </div>

    </div>
  </div>
</body>
</html>
