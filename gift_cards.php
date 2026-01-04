<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
$userId = (int)$_SESSION["user_id"];

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("
  SELECT gift_card_id, card_code, initial_value, remaining_value, created_at
  FROM gift_cards
  WHERE issued_to_visitor_id = ?
  ORDER BY gift_card_id DESC
");
$stmt->execute([$userId]);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("
  SELECT v.ID, u.Name
  FROM can_add c
  JOIN visitor v ON v.ID = c.Visitor2_ID
  LEFT JOIN user u ON u.ID = v.ID
  WHERE c.Visitor1_ID = ?
  ORDER BY u.Name ASC
");
$stmt->execute([$userId]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = (string)($_GET["msg"] ?? "");
$err = (string)($_GET["err"] ?? "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Gift Cards</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>
<body class="app-body">
<div class="app-page">
  <div class="app-card">
    <div class="app-title">My Gift Cards</div>
    <div class="app-sub">Send a gift card to a friend</div>

    <?php if ($msg): ?><div class="notice ok"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>

    <?php if (!$cards): ?>
      <p class="app-sub">You have no gift cards yet.</p>
     
    <?php else: ?>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Code</th>
              <th>Initial</th>
              <th>Remaining</th>
              <th>Gift To Friend</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($cards as $c): ?>
            <tr>
              <td><?= h((string)$c["card_code"]) ?></td>
              <td><?= (int)$c["initial_value"] ?></td>
              <td><?= (int)$c["remaining_value"] ?></td>
              <td>
                <?php if (!$friends): ?>
                  <span class="app-sub">No friends found</span>
                <?php else: ?>
                  <form method="POST" action="gift_card_send_submit.php" style="display:flex; gap:10px; align-items:center; margin:0;">
                    <input type="hidden" name="gift_card_id" value="<?= (int)$c["gift_card_id"] ?>">
                    <select name="to_visitor_id" required>
                      <option value="">Select friend</option>
                      <?php foreach ($friends as $f): ?>
                        <option value="<?= (int)$f["ID"] ?>"><?= h((string)($f["Name"] ?? ("User ".$f["ID"]))) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-ghost small" type="submit"
                      onclick="return confirm('Send this gift card to selected friend?');">
                      Send
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php endif; ?>

    <div style="margin-top:14px;">
      <a class="btn btn-ghost" href="shop.php">Go to Shop</a>
      <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>
