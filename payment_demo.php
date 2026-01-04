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
$pid = (int)($_GET["pid"] ?? 0);
if ($pid <= 0) die("Invalid purchase.");

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


$stmt = $pdo->prepare("
  SELECT P_ID, Amount, paid_amount
  FROM purchase
  WHERE P_ID = ? AND Visitor_ID = ?
  LIMIT 1
");
$stmt->execute([$pid, $userId]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) die("Purchase not found (or not yours).");


$stmt = $pdo->prepare("
  SELECT Payment_method
  FROM purchase_method
  WHERE P_ID = ?
  LIMIT 1
");
$stmt->execute([$pid]);
$method = (string)($stmt->fetchColumn() ?? "Unknown");

$demoOtp = "123456";
$payAmount = (int)($p["paid_amount"] ?? 0);


$methodKey = strtolower(trim($method));
$qrFile = "assets/qr_card.png"; 

if ($methodKey === "bkash" || $methodKey === "bKash") $qrFile = "assets/qr_bkash.png";
if ($methodKey === "nagad") $qrFile = "assets/qr_nagad.png";
if ($methodKey === "card")  $qrFile = "assets/qr_card.png";


$qrPathOnDisk = __DIR__ . "/" . $qrFile;
$qrSrc = file_exists($qrPathOnDisk) ? $qrFile : ("demo_qr.php?pid=" . (int)$pid);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Demo Payment</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
<div class="app-page">
  <div class="app-card">

    <div class="app-title">PAY BY QR</div>
    <div class="app-sub">
      Purchase ID: <b><?= (int)$p["P_ID"] ?></b><br>
      Method: <b><?= h($method) ?></b><br>
      Amount to pay: <b><?= $payAmount ?></b>
    </div>

    <hr style="margin:18px 0; opacity:.2">

    <?php if ($payAmount <= 0): ?>
      <div class="notice ok">No external payment needed (paid by points/balance).</div>
      <div style="margin-top:14px;">
        <a class="btn" href="purchase_success.php?pid=<?= (int)$p["P_ID"] ?>">Go to Receipt</a>
        <a class="btn btn-ghost" href="shop.php">Back to Shop</a>
      </div>
    <?php else: ?>

      
      <div style="margin:14px 0;">
        <div class="app-sub"><b>QR Code:</b></div>
        <img
          src="<?= h($qrSrc) ?>"
          alt="Payment QR"
          style="max-width:240px; border-radius:12px; border:1px solid rgba(255,255,255,.15); padding:10px; background:rgba(255,255,255,.05);"
        >
        <div class="app-sub" style="opacity:.85; margin-top:8px;">
          Scan in <b><?= h($method) ?></b> app .
        </div>
      </div>

      <form method="POST" action="payment_demo_confirm.php">
        <input type="hidden" name="pid" value="<?= (int)$pid ?>">

        <label class="app-label">Enter Demo OTP</label>
        <input
          class="app-input"
          name="otp"
          required
          inputmode="numeric"
          autocomplete="one-time-code"
          placeholder="Use <?= h($demoOtp) ?>"
        >

        <button class="btn" type="submit">Confirm Payment</button>
      </form>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="shop.php">Back to Shop</a>
        <a class="btn btn-ghost" href="purchase_history.php">History</a>
      </div>

      <div class="app-sub" style="margin-top:10px; opacity:.7;">
        OTP: <b><?= h($demoOtp) ?></b>
      </div>

    <?php endif; ?>

  </div>
</div>
</body>
</html>
 
