<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];
$msg = "";

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = (string)($_POST["action"] ?? "");
  $requestId = (int)($_POST["request_id"] ?? 0);

  $stmt = $pdo->prepare("
    SELECT requester_id, receiver_id, status
    FROM friend_requests
    WHERE ID = ? AND receiver_id = ?
    LIMIT 1
  ");
  $stmt->execute([$requestId, $userId]);
  $req = $stmt->fetch();
  

  if (!$req) {
    $msg = "Request not found.";
  } elseif ($req["status"] !== "pending") {
    $msg = "This request is already processed.";
  } else {
    $requesterId = (int)$req["requester_id"];

    if ($action === "accept") {
      $pdo->beginTransaction();
      try {
   
        $stmt = $pdo->prepare("
          UPDATE friend_requests
          SET status='accepted', responded_at=NOW()
          WHERE ID = ? AND receiver_id = ?
        ");
        $stmt->execute([$requestId, $userId]);

        $stmt = $pdo->prepare("
          INSERT IGNORE INTO can_add (Visitor1_ID, Visitor2_ID)
          VALUES (?, ?)
        ");
        $stmt->execute([$userId, $requesterId]);
        $stmt->execute([$requesterId, $userId]);

        $pdo->commit();
        $msg = "✅ Friend request accepted!";
      } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "❌ Could not accept request.";
      }

    } elseif ($action === "reject") {
      $stmt = $pdo->prepare("
        UPDATE friend_requests
        SET status='rejected', responded_at=NOW()
        WHERE ID = ? AND receiver_id = ?
      ");
      $stmt->execute([$requestId, $userId]);
      $msg = "Request rejected.";
    }
  }
}


$stmt = $pdo->prepare("
  SELECT fr.ID, fr.requester_id, fr.created_at, u.Name, u.Email
  FROM friend_requests fr
  JOIN user u ON u.ID = fr.requester_id
  WHERE fr.receiver_id = ? AND fr.status = 'pending'
  ORDER BY fr.created_at DESC
");
$stmt->execute([$userId]);
$pending = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Friend Requests</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title">Friend Requests</div>
      <div class="app-sub">Accept or reject friend requests.</div>

      <?php if ($msg): ?>
        <div class="notice"><?= h($msg) ?></div>
      <?php endif; ?>

      <?php if (!$pending): ?>
        <p class="app-sub">No pending requests right now.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>From</th>
                <th>Email</th>
                <th>Sent</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($pending as $r): ?>
              <tr>
                <td><?= h((string)$r["Name"]) ?></td>
                <td><?= h((string)$r["Email"]) ?></td>
                <td><?= h((string)$r["created_at"]) ?></td>
                <td style="display:flex; gap:8px; flex-wrap:wrap;">
                  <form method="POST" style="margin:0;">
                    <input type="hidden" name="request_id" value="<?= (int)$r["ID"] ?>">
                    <input type="hidden" name="action" value="accept">
                    <button class="btn" type="submit">Accept</button>
                  </form>

                  <form method="POST" style="margin:0;">
                    <input type="hidden" name="request_id" value="<?= (int)$r["ID"] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-ghost" type="submit">Reject</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-ghost" href="dashboard.php">Back</a>
        <a class="btn btn-ghost" href="friends.php">Friends</a>
      </div>
    </div>
  </div>
</body>
</html>

