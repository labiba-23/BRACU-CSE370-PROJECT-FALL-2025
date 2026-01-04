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

$pid = (int)($_GET["pid"] ?? 0);
if ($pid <= 0) {
    die("pid missing");
}

$stmt = $pdo->prepare("
    SELECT p.*, pm.Payment_method
    FROM purchase p
    LEFT JOIN purchase_method pm ON pm.P_ID = p.P_ID
    WHERE p.P_ID = ? AND p.Visitor_ID = ?
");
$stmt->execute([$pid, $userId]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    die("Purchase not found (or you don't have access).");
}


$stmt = $pdo->prepare("
    SELECT pr.name, pr.product_type, li.quantity, li.unit_price, li.line_total
    FROM purchase_line_items li
    JOIN products pr ON pr.product_id = li.product_id
    WHERE li.P_ID = ?
    ORDER BY pr.product_type, pr.name
");
$stmt->execute([$pid]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase Success</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">

      <div class="app-title">Purchase Successful ✅</div>
      <div class="app-sub">Receipt for your order</div>

      <hr style="margin:18px 0; opacity:.2">

      <div class="table-wrap">
        <table>
          <tbody>
            <tr><th style="text-align:left;">Purchase ID</th><td><?= (int)$purchase["P_ID"]; ?></td></tr>
            <tr><th style="text-align:left;">Date</th><td><?= h((string)$purchase["P_date"]); ?></td></tr>
            <tr><th style="text-align:left;">Payment Method</th><td><?= h((string)($purchase["Payment_method"] ?? "")); ?></td></tr>
          </tbody>
        </table>
      </div>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Summary</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Total</th>
              <th>Points Used</th>
              <th>Balance Used</th>
              <th>Remaining to Pay</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><?= (int)$purchase["Amount"]; ?></td>
              <td><?= (int)$purchase["points_used"]; ?></td>
              <td><?= (int)$purchase["wallet_used"]; ?></td>
              <td><?= (int)$purchase["paid_amount"]; ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Items</h3>
      <?php if (!$items): ?>
        <p class="app-sub">No items found.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= h((string)$it["name"]); ?></td>
                  <td><?= h((string)$it["product_type"]); ?></td>
                  <td><?= (int)$it["quantity"]; ?></td>
                  <td><?= (int)$it["unit_price"]; ?></td>
                  <td><?= (int)$it["line_total"]; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="purchase_history.php">View history</a>
        <a class="btn btn-ghost" href="shop.php">New purchase</a>
        <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
      </div>

    </div>
  </div>
</body>
</html>
