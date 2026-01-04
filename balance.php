<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];
$msg = "";
$isOk = true;

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

function getColumns(PDO $pdo, string $table): array {
  
  $stmt = $pdo->prepare("
    SELECT COLUMN_NAME
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = ?
  ");
  $stmt->execute([$table]);
  return array_map(fn($r) => $r["COLUMN_NAME"], $stmt->fetchAll(PDO::FETCH_ASSOC));
}


$stmt = $pdo->prepare("SELECT balance, Reward_points FROM visitor WHERE ID = ?");
$stmt->execute([$userId]);
$visitorRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$visitorRow) die("Visitor not found.");

$balance = (int)$visitorRow["balance"];
$rewardPoints = (int)$visitorRow["Reward_points"];


$purchases = [];
if (tableExists($pdo, "purchase")) {
  $stmt = $pdo->prepare("
    SELECT P_ID, P_date, Amount, points_used, wallet_used, paid_amount
    FROM purchase
    WHERE Visitor_ID = ?
    ORDER BY P_ID DESC
    LIMIT 10
  ");
  $stmt->execute([$userId]);
  $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$watchPartyReady =
  tableExists($pdo, "watch_parties") &&
  tableExists($pdo, "party_invites") &&
  tableExists($pdo, "groups");

$movieCol = null;
$invites  = [];
$ledger   = [];
$watchPartyNotice = "";

if ($watchPartyReady) {
  $cols = getColumns($pdo, "watch_parties");

  foreach (["movie_name", "movie_title", "movie_id"] as $c) {
    if (in_array($c, $cols, true)) { $movieCol = $c; break; }
  }

  if ($movieCol === null) {
    $watchPartyNotice = "watch_parties table is missing a movie column (movie_name/movie_title/movie_id).";
    $watchPartyReady = false;
  }
} else {
  $watchPartyNotice = "Watch party system tables are missing (watch_parties / party_invites / groups).";
}


if ($watchPartyReady && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["invite_action"])) {
  $inviteId = (int)($_POST["invite_id"] ?? 0);
  $action = (string)($_POST["invite_action"] ?? "");

  if ($inviteId <= 0 || !in_array($action, ["accept", "reject"], true)) {
    $msg = "Invalid action.";
    $isOk = false;
  } else {
    try {
      $pdo->beginTransaction();

      // Lock invite + party row
      $stmt = $pdo->prepare("
        SELECT pi.invite_id, pi.status AS invite_status, pi.visitor_id,
               wp.party_id, wp.cost, wp.`{$movieCol}` AS movie_value
        FROM party_invites pi
        JOIN watch_parties wp ON wp.party_id = pi.party_id
        WHERE pi.invite_id = ? AND pi.visitor_id = ?
        FOR UPDATE
      ");
      $stmt->execute([$inviteId, $userId]);
      $inv = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$inv) throw new Exception("Invite not found.");
      if ($inv["invite_status"] !== "pending") throw new Exception("This invite was already answered.");

      if ($action === "reject") {
        $stmt = $pdo->prepare("
          UPDATE party_invites
          SET status='rejected', responded_at=NOW()
          WHERE invite_id = ?
        ");
        $stmt->execute([$inviteId]);

        $pdo->commit();
        $msg = "Rejected. No balance deducted.";
        $isOk = true;

      } else {
        // Accept: lock balance
        $stmt = $pdo->prepare("SELECT balance FROM visitor WHERE ID = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $balNow = (int)$stmt->fetchColumn();

        $cost = (int)$inv["cost"];
        if ($balNow < $cost) {
          throw new Exception("Not enough balance to accept (need {$cost}).");
        }

        // Deduct balance
        $stmt = $pdo->prepare("UPDATE visitor SET balance = balance - ? WHERE ID = ?");
        $stmt->execute([$cost, $userId]);

        // Mark accepted
        $stmt = $pdo->prepare("
          UPDATE party_invites
          SET status='accepted', responded_at=NOW()
          WHERE invite_id = ?
        ");
        $stmt->execute([$inviteId]);

        // Ledger
        if (tableExists($pdo, "balance_ledger")) {
          $stmt = $pdo->prepare("
            INSERT INTO balance_ledger (visitor_id, party_id, change_amount, reason)
            VALUES (?, ?, ?, ?)
          ");
          $stmt->execute([
            $userId,
            (int)$inv["party_id"],
            -$cost,
            "Accepted watch party: " . (string)$inv["movie_value"]
          ]);
        }

        $pdo->commit();
        $msg = "Accepted! Balance deducted by {$cost}.";
        $isOk = true;
      }

    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $msg = $e->getMessage();
      $isOk = false;
    }
  }

  $stmt = $pdo->prepare("SELECT balance, Reward_points FROM visitor WHERE ID = ?");
  $stmt->execute([$userId]);
  $visitorRow = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($visitorRow) {
    $balance = (int)$visitorRow["balance"];
    $rewardPoints = (int)$visitorRow["Reward_points"];
  }
}


if ($watchPartyReady) {
  $sqlInv = "
    SELECT pi.invite_id, pi.status,
           wp.party_id, wp.`{$movieCol}` AS movie_value, wp.cost, wp.created_at,
           g.group_name
    FROM party_invites pi
    JOIN watch_parties wp ON wp.party_id = pi.party_id
    JOIN `groups` g ON g.group_id = wp.group_id
    WHERE pi.visitor_id = ?
    ORDER BY (pi.status='pending') DESC, wp.created_at DESC
  ";
  $stmt = $pdo->prepare($sqlInv);
  $stmt->execute([$userId]);
  $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


if (tableExists($pdo, "balance_ledger")) {
  $stmt = $pdo->prepare("
    SELECT change_amount, reason, created_at
    FROM balance_ledger
    WHERE visitor_id = ?
    ORDER BY created_at DESC
    LIMIT 30
  ");
  $stmt->execute([$userId]);
  $ledger = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Balance</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>
<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title">Balance</div>
      <div class="app-sub">Your wallet & activity</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <h3>Your Wallet</h3>

      <div style="display:flex; gap:18px; flex-wrap:wrap; margin:10px 0 16px;">
        <div>
          <div class="app-sub">Balance</div>
          <div style="font-size:34px; font-weight:900; margin-top:4px;"><?= (int)$balance ?></div>
        </div>
        <div>
          <div class="app-sub">Reward Points</div>
          <div style="font-size:34px; font-weight:900; margin-top:4px;"><?= (int)$rewardPoints ?></div>
        </div>
      </div>

      <div class="row" style="margin: 6px 0 14px;">
        <a class="btn btn-ghost" href="shop.php">Shop</a>
        <a class="btn btn-ghost" href="purchase_history.php">Purchase History</a>
        <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
      </div>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Recent Purchases</h3>
      <?php if (!$purchases): ?>
        <p class="app-sub">No purchases yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>P_ID</th>
                <th>Date</th>
                <th>Total</th>
                <th>Points Used</th>
                <th>Balance Used</th>
                <th>Remaining</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($purchases as $p): ?>
                <tr>
                  <td><?= (int)$p["P_ID"] ?></td>
                  <td><?= h((string)$p["P_date"]) ?></td>
                  <td><?= (int)$p["Amount"] ?></td>
                  <td><?= (int)$p["points_used"] ?></td>
                  <td><?= (int)$p["wallet_used"] ?></td>
                  <td><?= (int)$p["paid_amount"] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Notifications (Watch Party Invites)</h3>
      <?php if (!$watchPartyReady): ?>
        <div class="notice err">
          Watch party section disabled: <?= h($watchPartyNotice) ?>
        </div>
      <?php else: ?>
        <?php if (!$invites): ?>
          <p class="app-sub">No invites yet.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Group</th>
                  <th>Movie</th>
                  <th>Cost</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($invites as $i): ?>
                  <tr>
                    <td><?= h((string)$i["group_name"]) ?></td>
                    <td><?= h((string)$i["movie_value"]) ?></td>
                    <td><?= (int)$i["cost"] ?></td>
                    <td><?= h((string)$i["status"]) ?></td>
                    <td>
                      <?php if ($i["status"] === "pending"): ?>
                        <form method="POST" style="display:inline-block; margin:0;">
                          <input type="hidden" name="invite_id" value="<?= (int)$i["invite_id"] ?>">
                          <button class="btn btn-ghost small" name="invite_action" value="accept" type="submit">Accept</button>
                        </form>
                        <form method="POST" style="display:inline-block; margin:0;"
                              onsubmit="return confirm('Reject this invite? No balance will be deducted.');">
                          <input type="hidden" name="invite_id" value="<?= (int)$i["invite_id"] ?>">
                          <button class="btn btn-ghost small" name="invite_action" value="reject" type="submit">Reject</button>
                        </form>
                      <?php endif; ?>
                      <a class="btn btn-ghost small" href="party.php?party_id=<?= (int)$i["party_id"] ?>">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Balance History</h3>
      <?php if (!$ledger): ?>
        <p class="app-sub">No balance activity yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Change</th>
                <th>Reason</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ledger as $l): ?>
                <tr>
                  <td><?= (int)$l["change_amount"] ?></td>
                  <td><?= h((string)$l["reason"]) ?></td>
                  <td><?= h((string)$l["created_at"]) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>
</body>
</html>
